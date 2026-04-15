<?php

namespace MlabPs\AwsUploadAssets\Controllers;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use MlabPs\AwsUploadAssets\Services\S3Uploader;

class ModuleController
{
    private \Context $context;
    private string $moduleName = 'mlab_aws_upload_assets';
    private \Module $module;
    private string $modulePath;
    private ?S3Uploader $s3Uploader = null;
    

    public function __construct(\Module $module, string $moduleName, string $modulePath)
    {
        $this->context = \Context::getContext();
        $this->module = $module;
        $this->moduleName = $moduleName;
        $this->modulePath = $modulePath;
    }

    /**
     * Ottiene l'istanza del servizio S3Uploader
     */
    private function getS3Uploader(): S3Uploader
    {
        if ($this->s3Uploader === null) {
            $this->s3Uploader = new S3Uploader(
                \Configuration::get('AWS_S3_BUCKET'),
                \Configuration::get('AWS_ACCESS_KEY_ID'),
                \Configuration::get('AWS_SECRET_ACCESS_KEY'),
                \Configuration::get('AWS_REGION', 'eu-south-1'),
                \Configuration::get('AWS_S3_PATH_PREFIX', 'products/')
            );
        }
        return $this->s3Uploader;
    }

    /**
     * Gestisce la rigenerazione delle immagini
     * Questo hook viene chiamato per ogni immagine con tutti i suoi tagli
     */
    public function handleImageRegeneration($params)
    {
        try {
            // Il parametro può contenere informazioni sull'immagine rigenerata
            if (isset($params['id_image'])) {
                $imageId = $params['id_image'];
                $this->uploadProductImageAllSizes($imageId);
            }
            
            return true;
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'AWS Upload - Image Regeneration Error: ' . $e->getMessage(),
                3,
                null,
                'MlabAwsUploadAssets'
            );
            return false;
        }
    }

    /**
     * Gestisce l'upload di un'immagine
     */
    public function handleImageUpload($params)
    {
        try {
            if (isset($params['id_image'])) {
                $imageId = $params['id_image'];
                $this->uploadProductImageAllSizes($imageId);
            }
            
            return true;
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'AWS Upload - Image Upload Error: ' . $e->getMessage(),
                3,
                null,
                'MlabAwsUploadAssets'
            );
            return false;
        }
    }

    /**
     * Gestisce l'aggiornamento di un'immagine prodotto
     */
    public function handleProductImageUpdate($params)
    {
        try {
            if (isset($params['id_image'])) {
                $imageId = $params['id_image'];
                $this->uploadProductImageAllSizes($imageId);
            }
            
            return true;
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'AWS Upload - Product Image Update Error: ' . $e->getMessage(),
                3,
                null,
                'MlabAwsUploadAssets'
            );
            return false;
        }
    }

    /**
     * Carica tutte le versioni (tagli) di un'immagine prodotto su S3
     */
    private function uploadProductImageAllSizes(int $imageId)
    {
        $image = new \Image($imageId);
        if (!\Validate::isLoadedObject($image)) {
            throw new \Exception("Image ID {$imageId} not found");
        }

        $product = new \Product($image->id_product);
        if (!\Validate::isLoadedObject($product)) {
            throw new \Exception("Product not found for image ID {$imageId}");
        }

        // Ottieni tutti i tipi di immagine configurati
        $imageTypes = \ImageType::getImagesTypes('products');
        
        // Upload dell'immagine originale
        $this->uploadImageFile($image, $product, null);

        // Upload di tutti i tagli dell'immagine
        foreach ($imageTypes as $imageType) {
            $this->uploadImageFile($image, $product, $imageType);
        }
    }

    /**
     * Carica un singolo file immagine su S3
     */
    private function uploadImageFile(\Image $image, \Product $product, ?array $imageType = null)
    {
        $imagePath = $image->getPathForCreation();
        $fileExtension = $image->image_format; // Es. 'jpg', 'png'
        
        if ($imageType === null) {
            // Immagine originale
            $fullPath = $imagePath . '.' . $fileExtension;
            $s3Key = sprintf(
                '%d/%d.%s',
                $product->id,
                $image->id,
                $fileExtension
            );
        } else {
            // Immagine ridimensionata
            $fullPath = $imagePath . '-' . $imageType['name'] . '.' . $fileExtension;
            $s3Key = sprintf(
                '%d/%d-%s.%s',
                $product->id,
                $image->id,
                $imageType['name'],
                $fileExtension
            );
        }

        if (!file_exists($fullPath)) {
            \PrestaShopLogger::addLog(
                "AWS Upload - File not found: {$fullPath}",
                2,
                null,
                'MlabAwsUploadAssets'
            );
            return;
        }

        try {
            $this->getS3Uploader()->uploadFile($fullPath);
            
            \PrestaShopLogger::addLog(
                "AWS Upload - Successfully uploaded: {$s3Key}",
                1,
                null,
                'MlabAwsUploadAssets'
            );
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                "AWS Upload - Failed to upload {$s3Key}: " . $e->getMessage(),
                3,
                null,
                'MlabAwsUploadAssets'
            );
            throw $e;
        }
    }

    public function displayCookieBanner()
    {
        return $this->renderTemplate('cookie_banner.tpl');
    }

    /**
     * Genera il form di configurazione
     */
    private function displayConfigurationForm(): string
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Configurazione AWS S3'),
                    'icon' => 'icon-cloud-upload'
                ],
                'description' => $this->description(),
                'input' => $this->getConfigurationFields(),
                'submit' => [
                    'title' => $this->module->l('Salva'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new \HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->moduleName;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = 'id_configuration';
        $helper->submit_action = 'submit' . $this->moduleName;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->moduleName . '&tab_module=back_office_features&module_name=' . $this->moduleName;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        // Carica i valori correnti dalla configurazione
        $currentValues = [];
        foreach ($this->getConfigurationFields() as $field) {
            if (isset($field['name'])) {
                $defaultValue = isset($field['default']) ? $field['default'] : '';
                $currentValues[$field['name']] = \Configuration::get($field['name'], $defaultValue);
            }
        }

        $helper->tpl_vars = [
            'fields_value' => $currentValues,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /**
     * Configurazioni disponibili per il modulo
     */
    public function getConfigurationFields(): array
    {
        return [
            [
                'type' => 'text',
                'label' => $this->module->l('AWS Access Key ID'),
                'name' => 'AWS_ACCESS_KEY_ID',
                'class' => 'fixed-width-xxl',
                'required' => true,
                'desc' => $this->module->l('La tua AWS Access Key ID')
            ],
            [
                'type' => 'text',
                'label' => $this->module->l('AWS Secret Access Key'),
                'name' => 'AWS_SECRET_ACCESS_KEY',
                'class' => 'fixed-width-xxl',
                'required' => true,
                'desc' => $this->module->l('La tua AWS Secret Access Key')
            ],
            [
                'type' => 'text',
                'label' => $this->module->l('AWS Region'),
                'name' => 'AWS_REGION',
                'class' => 'fixed-width-m',
                'required' => true,
                'desc' => $this->module->l('Regione AWS (es. eu-south-1, us-east-1)'),
                'default' => 'eu-south-1'
            ],
            [
                'type' => 'text',
                'label' => $this->module->l('S3 Bucket Name'),
                'name' => 'AWS_S3_BUCKET',
                'class' => 'fixed-width-xl',
                'required' => true,
                'desc' => $this->module->l('Nome del bucket S3 dove caricare le immagini')
            ],
            [
                'type' => 'text',
                'label' => $this->module->l('S3 Path Prefix'),
                'name' => 'AWS_S3_PATH_PREFIX',
                'class' => 'fixed-width-l',
                'required' => false,
                'desc' => $this->module->l('Prefisso del percorso nel bucket (es. products/)'),
                'default' => 'products/'
            ],
        ];
    }
    
    private function description(): string
    {
        return $this->module->l('Configura le credenziali AWS per caricare automaticamente le immagini dei prodotti su S3. Le immagini verranno caricate ogni volta che vengono aggiunte o rigenerate dal back office.');
    }

    /**
     * Renderizza un template Smarty
     */
    private function renderTemplate(string $templateName): string
    {
        $templatePath = _PS_MODULE_DIR_ . $this->moduleName . '/views/templates/admin/' . $templateName;

        if (!file_exists($templatePath)) {
            \PrestaShopLogger::addLog(
                "Template not found: {$templatePath}",
                3,
                null,
                'MlabAwsUploadAssets'
            );
            return '';
        }

        return $this->context->smarty->fetch($templatePath);
    }

    public function handleConfiguration()
    {
        try {
            $output = '';
            
            // Gestione del submit del form
            if (\Tools::isSubmit('submit' . $this->moduleName)) {
                $errors = [];
                
                foreach ($this->getConfigurationFields() as $field) {
                    if (isset($field['name'])) {
                        $value = \Tools::getValue($field['name'], '');
                        
                        // Validazione campi required
                        if (isset($field['required']) && $field['required'] && empty($value)) {
                            $errors[] = sprintf(
                                $this->module->l('Il campo "%s" è obbligatorio'),
                                $field['label']
                            );
                        } else {
                            \Configuration::updateValue($field['name'], $value);
                        }
                    }
                }
                
                if (empty($errors)) {
                    $output .= $this->module->displayConfirmation(
                        $this->module->l('Impostazioni salvate con successo.')
                    );
                    
                    // Test della connessione S3
                    try {
                        $s3Uploader = $this->getS3Uploader();
                        if ($s3Uploader->testConnection()) {
                            $output .= $this->module->displayConfirmation(
                                $this->module->l('Connessione AWS S3 testata con successo!')
                            );
                        }
                    } catch (\Exception $e) {
                        $output .= $this->module->displayWarning(
                            $this->module->l('Configurazione salvata ma errore nel test della connessione S3: ') . $e->getMessage()
                        );
                    }
                } else {
                    foreach ($errors as $error) {
                        $output .= $this->module->displayError($error);
                    }
                }
            }
            
            return $output . $this->displayConfigurationForm();
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'AWS Upload - Configuration Error: ' . $e->getMessage(),
                3,
                null,
                'MlabAwsUploadAssets'
            );
            return $this->module->displayError(
                $this->module->l('Si è verificato un errore durante il caricamento della configurazione.')
            );
        }
    }
}