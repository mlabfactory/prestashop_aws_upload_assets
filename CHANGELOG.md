# Changelog

Tutte le modifiche rilevanti a questo progetto saranno documentate in questo file.

## [1.0.0] - 2024

### Aggiunto
- Upload automatico di immagini prodotto su AWS S3
- Supporto per tutti i formati/tagli immagine PrestaShop (small, medium, large, cart, home, etc.)
- Hook `actionWatermark` per intercettare la rigenerazione delle immagini
- Hook `actionAfterImageUpload` per intercettare l'upload di nuove immagini
- Hook `actionAfterUpdateProductImage` per intercettare l'aggiornamento immagini
- Service class `S3Uploader` per gestire le operazioni AWS S3
- Form di configurazione nel back office per credenziali AWS
- Test automatico della connessione S3 al salvataggio configurazione
- Logging dettagliato tramite PrestaShopLogger
- Gestione errori con try-catch e logging centralizzato
- Documentazione completa in README.md
- Guida installazione passo-passo in INSTALL.md
- Script di validazione (validate.sh)
- File .env.example per configurazione credenziali

### Modificato
- Namespace da `MlabPs\CookiePolicyModule` a `MlabPs\AwsUploadAssets`
- Nome modulo da cookie policy a upload assets
- Hook da display (frontend) ad action (backend)
- Tab modulo da `front_office_features` a `back_office_features`
- Campi configurazione per credenziali AWS invece di cookie policy

### Rimosso
- Logica cookie policy banner
- Hook displayHeader, displayFooter, displayFooterAfter
- Template Smarty per cookie banner
- Configurazioni cookie policy
- File TypeScript/JavaScript per frontend

### Tecnico
- Architettura PSR-4 con autoloading Composer
- Pattern Service Layer per separazione logica business
- Dependency Injection per AWS SDK
- Compatibilità PrestaShop 8.x - 9.x
- AWS SDK PHP ^3.357
- Gestione file con stream per ottimizzare memoria
- Content-Type automatico basato su estensione file
- ACL public-read per accessibilità immagini

### Sicurezza
- Credenziali AWS salvate in Configuration (database PrestaShop)
- File .env escluso da git
- Validazione input configurazione
- Gestione sicura errori AWS

## Note di Migrazione

Questo modulo è stato completamente refactored da un modulo cookie policy.
Non ci sono migrazioni da versioni precedenti.

## Breaking Changes

- Nessuno (prima release)

## Known Issues

- Nessuno noto

## Roadmap Futuro

Possibili funzionalità future:
- [ ] Eliminazione immagini da S3 quando eliminate da PrestaShop
- [ ] Sincronizzazione batch di immagini esistenti
- [ ] Supporto per altri tipi di asset (PDF, video, etc.)
- [ ] CDN CloudFront integration
- [ ] Compressione immagini prima dell'upload
- [ ] Conversione formato WebP
- [ ] Dashboard statistiche upload
- [ ] Backup automatico su S3
- [ ] Multi-bucket support
- [ ] Lazy upload (queue system)
