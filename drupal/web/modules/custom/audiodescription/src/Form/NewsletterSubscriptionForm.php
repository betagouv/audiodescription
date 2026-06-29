<?php

namespace Drupal\audiodescription\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;

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

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  public function __construct(ConfigPagesLoaderServiceInterface $configPagesLoader, ClientInterface $httpClient) {
    $this->configPagesLoader = $configPagesLoader;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config_pages.loader'),
      $container->get('http_client'),
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
    $accountId = Settings::get('sendethic_account_id');
    $apiKey = Settings::get('sendethic_api_key');
    $email = $form_state->getUserInput()['email'];

    try {
      $this->httpClient->post('https://services.message-business.com/api/rest/v4/contact/attribute', [
        'auth' => [$accountId, $apiKey],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'id' => 0,
          'contactKey' => $email,
          'attributes' => [
            ['id' => 'email', 'fieldName' => 'email', 'fieldValue' => $email],
          ],
        ],
      ]);
      $form_state->setRedirect('audiodescription.newsletter.confirmation');
    }
    catch (\Exception $e) {
      \Drupal::logger('audiodescription')->error('Sendethic API error: @message', ['@message' => $e->getMessage()]);
      $this->messenger()->addError($this->t('Une erreur est survenue lors de votre inscription. Veuillez réessayer.'));
    }
  }

}
