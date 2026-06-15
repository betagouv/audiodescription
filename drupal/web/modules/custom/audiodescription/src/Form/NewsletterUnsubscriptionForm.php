<?php

namespace Drupal\audiodescription\Form;

use Brevo\Brevo;
use Brevo\Contacts\Requests\DeleteContactRequest;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;

/**
 * Provides a form for unsubscribing from the newsletter.
 */
class NewsletterUnsubscriptionForm extends FormBase implements TrustedCallbackInterface {

  /**
   * The config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  public function __construct(ConfigPagesLoaderServiceInterface $configPagesLoader) {
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config_pages.loader'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsletter_unsubscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['novalidate'] = 'novalidate';

    $form['#attached']['library'][] = 'audiodescription/newsletter_form';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adresse e-mail à désinscrire (champ obligatoire)'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#description' => 'exemple : prenom.nom@gmail.com',
      '#attributes' => [
        'class' => [
          'fr-col-12 fr-col-md-6',
        ],
        'autocomplete' => 'email',
      ],
      '#element_validate' => [],
      '#pre_render' => array_merge(
        \Drupal::service('element_info')->getInfoProperty('email', '#pre_render', []),
        [[static::class, 'removeAriaDescribedBy']],
      ),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Se désinscrire"),
      '#attributes' => [
        'class' => [
          'fr-btn',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['removeAriaDescribedBy'];
  }

  /**
   * Removes aria-describedby from the element's attributes.
   */
  public static function removeAriaDescribedBy(array $element): array {
    unset($element['#attributes']['aria-describedby']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    if (empty($email)) {
      $this->messenger()->addError(
        $this->t('Le champ @name est requis.', ['@name' => $form['email']['#title']])
      );
    }
    elseif (!\Drupal::service('email.validator')->isValid($email)) {
      $message = $this->t('@name n\'est pas valide. Saisir une adresse e-mail, par exemple : prenom.nom@gmail.com', [
        '@name' => $form['email']['#title'],
      ]);
      $form_state->setErrorByName('email', $message);
      $this->messenger()->addError($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $apiKey = $newsletter->get('field_news_api_key')->value;

    $brevo = new Brevo($apiKey);

    $email = $form_state->getUserInput()['email'];

    try {
      $brevo->contacts->deleteContact($email, new DeleteContactRequest(['identifierType' => 'email_id']));
      $form_state->setRedirect('audiodescription.newsletter.unsubscription.confirmation');
    }
    catch (\Exception $e) {
      echo 'Exception when calling ContactsApi->deleteContact: ', $e->getMessage(), PHP_EOL;
    }
  }

}
