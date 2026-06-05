<?php

namespace Drupal\audiodescription\Form;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateContact;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;

/**
 * Provides a form for searching movies.
 */
class NewsletterSubscriptionForm extends FormBase implements TrustedCallbackInterface {

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
    return 'newsletter_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['novalidate'] = 'novalidate';

    $config_pages = $this->configPagesLoader;
    $newsletter = $config_pages->load('newsletter');

    $form['#attached']['library'][] = 'audiodescription/newsletter_form';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adresse e-mail (champ obligatoire)'),
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

    if ($newsletter && !$newsletter->get('field_newsletter_infos_rgpd')->isEmpty()) {
      $field = $newsletter->get('field_newsletter_infos_rgpd');
      if (!empty($field->processed)) {
        $markup = Markup::create('<div>' . $field->processed . '</div>');
        $form['infos_rgpd'] = [
          '#type' => 'markup',
          '#markup' => $markup,
        ];
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("S’inscrire"),
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
      // Let Drupal's #required validation set the form error and inline message.
      // Add the same standard message to the messenger for the global block.
      $this->messenger()->addError(
        $this->t('@name field is required.', ['@name' => $form['email']['#title']])
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

    // Configure API key authorization: api-key.
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);

    // Pass a custom http client implementing GuzzleHttp\ClientInterface,
    // or use the default GuzzleHttp\Client.
    $apiInstance = new ContactsApi(
      new Client(),
      $config
    );

    $email = $form_state->getUserInput()['email'];
    $list = (int) $newsletter->get('field_news_list')->value;

    $createContact = new CreateContact([
      'email' => $email,
      'updateEnabled' => TRUE,
      'listIds' => [$list],
    ]);

    try {
      $apiInstance->createContact($createContact);
      $form_state->setRedirect('audiodescription.newsletter.confirmation');
    }
    catch (\Exception $e) {
      echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
    }
  }

}
