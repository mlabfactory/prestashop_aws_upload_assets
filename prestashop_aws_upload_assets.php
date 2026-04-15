<?php

/**
 * Prestashop Module to upload product images to AWS S3
 * @author mlabfactory <tech@mlabfactory.com>
 * MlabPs - AWS Upload Assets Module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Carica l'autoloader di Composer PRIMA di qualsiasi use statement
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Autoloader alternativo manuale
    spl_autoload_register(function ($className) {
        $prefix = 'MlabPs\\AwsUploadAssets\\';
        $base_dir = __DIR__ . '/src/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($className, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

// IMPORTANTE: use statement DOPO l'autoloader
use MlabPs\AwsUploadAssets\Controllers\ModuleController;


class prestashop_aws_upload_assets extends Module
{
    private $moduleController;

    public function __construct()
    {
        $this->name = 'prestashop_aws_upload_assets';
        $this->tab = 'back_office_features';
        $this->version = '1.0.0';
        $this->author = 'mlabfactory';
        $this->need_instance = 0;
        $this->_path = dirname(__FILE__) . '/';
        
        // Compatibilità PS9
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99'
        ];
        
        $this->bootstrap = true;

        // Chiamata SEMPRE sicura al parent constructor
        parent::__construct();

        $this->displayName = $this->l('MLab AWS Upload Assets Module');
        $this->description = $this->l('Upload product images to AWS S3');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        // Inizializza i controller solo dopo il parent constructor
        $this->initializeControllers();
    }

    /**
     * Inizializza i controller del modulo
     */
    private function initializeControllers()
    {
        try {
            $this->moduleController = new ModuleController($this, $this->name, $this->_path);
        } catch (Exception $e) {
            // Log dell'errore per debug
            error_log("Errore inizializzazione controller: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ottiene il widget controller, inizializzandolo se necessario
     */
    private function getModuleController(): ModuleController
    {
        if (!$this->moduleController) {
            $this->initializeControllers();
        }
        return $this->moduleController;
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionWatermark') && // Quando le immagini vengono rigenerate
            $this->registerHook('actionAfterImageUpload') && // Dopo l'upload di un'immagine
            $this->registerHook('actionAfterUpdateProductImage') && // Dopo l'aggiornamento di un'immagine prodotto
            $this->registerHook('actionOnImageCutAfter') && // Dopo il ritaglio di un'immagine
            $this->registerHook('actionImageFormat') && // Quando viene generato un formato immagine specifico
            $this->registerHook('actionOnImageResize') && // Quando l'immagine viene ridimensionata/generata dinamicamente
            $this->registerHook('actionAfterGenerateWebpImage'); // Dopo la generazione di immagini WebP
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Hook chiamato quando le immagini vengono rigenerate
     * Questo hook viene chiamato per ogni immagine rigenerata con tutti i suoi tagli
     */
    public function hookActionWatermark($params)
    {
        return $this->getModuleController()->handleImageRegeneration($params);
    }

    /**
     * Hook chiamato dopo il ritaglio di un'immagine
     */
    public function hookActionOnImageCutAfter($params)
    {
        return $this->getModuleController()->handleImageCut($params);
    }

    /**
     * Hook chiamato dopo l'upload di un'immagine
     */
    public function hookActionAfterImageUpload($params)
    {
        return $this->getModuleController()->handleImageUpload($params);
    }

    /**
     * Hook chiamato dopo l'aggiornamento di un'immagine prodotto
     */
    public function hookActionAfterUpdateProductImage($params)
    {
        return $this->getModuleController()->handleProductImageUpdate($params);
    }

    /**
     * Hook chiamato quando viene generato un formato immagine specifico
     */
    public function hookActionImageFormat($params)
    {
        return $this->getModuleController()->handleImageFormat($params);
    }

    /**
     * Hook chiamato quando l'immagine viene ridimensionata/generata dinamicamente
     */
    public function hookActionOnImageResize($params)
    {
        return $this->getModuleController()->handleImageResize($params);
    }

    /**
     * Hook chiamato dopo la generazione di immagini WebP
     */
    public function hookActionAfterGenerateWebpImage($params)
    {
        return $this->getModuleController()->handleWebpGeneration($params);
    }

    /**
     * Configurazione del modulo
     */
    public function getContent()
    {
        return $this->getModuleController()->handleConfiguration();
    }
}