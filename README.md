# MLab AWS Upload Assets - PrestaShop Module

Modulo PrestaShop per il caricamento automatico delle immagini prodotto su AWS S3.

## Descrizione

Questo modulo carica automaticamente tutte le immagini dei prodotti (originali e tutti i tagli/formati) su un bucket AWS S3 ogni volta che:
- Un'immagine viene caricata dal back office
- Le immagini vengono rigenerate
- Un'immagine prodotto viene aggiornata

## Caratteristiche

- ✅ Caricamento automatico su AWS S3
- ✅ Supporto per tutti i formati immagine PrestaShop
- ✅ Upload di tutte le versioni ridimensionate (thumbnails, medium, large, etc.)
- ✅ Configurazione semplice tramite interfaccia back office
- ✅ Test della connessione AWS
- ✅ Log dettagliati per debugging
- ✅ Compatibile con PrestaShop 8.x e 9.x
- ✅ Architettura modulare con pattern PSR-4

## Requisiti

- PrestaShop 8.0.0 o superiore
- PHP 7.4 o superiore
- Composer
- Account AWS con accesso a S3
- Bucket S3 configurato

## Installazione

### 1. Installazione Composer

```bash
cd modules/mlab_aws_upload_assets
composer install
```

### 2. Installazione del modulo

1. Carica la cartella del modulo in `modules/mlab_aws_upload_assets`
2. Vai nel back office di PrestaShop
3. Naviga in: Moduli > Module Manager
4. Cerca "MLab AWS Upload Assets"
5. Clicca su "Installa"

## Configurazione AWS

### Creazione Bucket S3

1. Accedi alla Console AWS
2. Vai su S3
3. Crea un nuovo bucket o usa uno esistente
4. Configura i permessi per consentire l'accesso pubblico alle immagini (se necessario)

### Creazione Credenziali IAM

1. Vai su IAM nella Console AWS
2. Crea un nuovo utente con accesso programmatico
3. Assegna la policy con permessi S3 (sostituisci `nome-tuo-bucket` con il tuo bucket)
4. Salva le credenziali (Access Key ID e Secret Access Key)

## Configurazione Modulo

1. Vai nel back office di PrestaShop
2. Naviga in: Moduli > Module Manager
3. Cerca "MLab AWS Upload Assets"
4. Clicca su "Configura"
5. Inserisci i seguenti parametri:

- **AWS Access Key ID**: La tua Access Key
- **AWS Secret Access Key**: La tua Secret Key
- **AWS Region**: La regione del bucket (es. `eu-south-1`, `us-east-1`)
- **S3 Bucket Name**: Nome del bucket S3
- **S3 Path Prefix**: Prefisso del percorso (es. `products/` - opzionale)

6. Clicca su "Salva"

## Funzionamento

### Upload Immagini

Quando un'immagine prodotto viene caricata o rigenerata, il modulo intercetta l'evento e carica automaticamente tutti i formati su S3.

### Esempio Struttura S3

```
bucket-name/
└── products/
    ├── 1/
    │   ├── 1.jpg
    │   ├── 1-small_default.jpg
    │   ├── 1-medium_default.jpg
    │   └── ...
    └── ...
```

## Log e Debugging

I log sono salvati nel sistema di log di PrestaShop: Back Office > Strumenti avanzati > Log

## Supporto

Email: tech@mlabfactory.it

## Licenza

Copyright © 2024 MLab Factory

## Changelog

### v1.0.0 (2024)
- Release iniziale
- Upload automatico immagini prodotto su S3
