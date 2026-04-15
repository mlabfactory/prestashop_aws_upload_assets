# Guida Rapida - Installazione e Configurazione

## 1. Installazione Dipendenze

```bash
cd modules/mlab_aws_upload_assets
composer install
```

## 2. Verifica File Installati

Assicurati che la struttura sia:
```
mlab_aws_upload_assets/
├── src/
│   ├── Controllers/
│   │   └── ModuleController.php
│   └── Services/
│       └── S3Uploader.php
├── vendor/
├── mlab_aws_upload_assets.php
├── composer.json
└── config.json
```

## 3. Installa il Modulo in PrestaShop

1. Nel back office vai su: **Moduli > Module Manager**
2. Cerca **"MLab AWS Upload Assets"**
3. Clicca **"Installa"**

## 4. Configura AWS S3

### 4.1 Crea o seleziona un Bucket S3

1. Accedi alla Console AWS
2. Vai su **S3**
3. Crea un nuovo bucket o usa uno esistente
4. Annota il nome del bucket e la regione (es. `eu-south-1`)

### 4.2 Configura i Permessi del Bucket

Se vuoi che le immagini siano accessibili pubblicamente:

1. Nel bucket vai su **Permissions**
2. Disabilita "Block all public access" (opzionale)
3. Aggiungi una Bucket Policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::NOME-TUO-BUCKET/products/*"
        }
    ]
}
```

### 4.3 Crea Credenziali IAM

1. Vai su **IAM** nella Console AWS
2. Clicca **Users > Add user**
3. Nome: `prestashop-s3-uploader`
4. Access type: **Programmatic access**
5. Permissions: **Attach existing policies directly**
6. Crea una nuova policy con questi permessi:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:PutObjectAcl",
                "s3:GetObject",
                "s3:DeleteObject"
            ],
            "Resource": "arn:aws:s3:::NOME-TUO-BUCKET/*"
        },
        {
            "Effect": "Allow",
            "Action": "s3:ListBucket",
            "Resource": "arn:aws:s3:::NOME-TUO-BUCKET"
        }
    ]
}
```

7. Salva **Access Key ID** e **Secret Access Key**

## 5. Configura il Modulo in PrestaShop

1. Nel back office vai su: **Moduli > Module Manager**
2. Cerca **"MLab AWS Upload Assets"**
3. Clicca **"Configura"**
4. Inserisci:
   - **AWS Access Key ID**: La tua access key
   - **AWS Secret Access Key**: La tua secret key
   - **AWS Region**: es. `eu-south-1`
   - **S3 Bucket Name**: Nome del tuo bucket
   - **S3 Path Prefix**: `products/` (opzionale)
5. Clicca **"Salva"**

## 6. Test

### Test Automatico
Quando salvi la configurazione, il modulo testa automaticamente la connessione.

### Test Manuale
1. Vai su **Catalogo > Prodotti**
2. Seleziona un prodotto
3. Carica una nuova immagine
4. Controlla il bucket S3 - dovresti vedere le immagini caricate in:
   ```
   products/{product_id}/{image_id}.jpg
   products/{product_id}/{image_id}-small_default.jpg
   products/{product_id}/{image_id}-medium_default.jpg
   ...
   ```

## 7. Rigenerazione Immagini Esistenti

Se hai prodotti con immagini già caricate, puoi rigenerarle per caricarle su S3:

1. Vai su **Design > Immagini**
2. Seleziona **"Prodotti"**
3. Clicca **"Rigenera miniature"**
4. Le immagini verranno automaticamente caricate su S3

## Troubleshooting

### Errore: "AWS S3 Connection Test Failed"
- Verifica le credenziali AWS
- Controlla che il bucket esista nella regione corretta
- Verifica i permessi IAM

### Le immagini non vengono caricate
- Controlla i log: **Strumenti avanzati > Log**
- Verifica che il modulo sia installato
- Controlla i permessi delle cartelle `img/p/`

### Errore permessi S3
- Verifica la policy IAM
- Controlla i permessi del bucket
- Assicurati che l'ACL `public-read` sia permesso

## Log

I log del modulo si trovano in:
**Back Office > Strumenti avanzati > Log**

Cerca: `MlabAwsUploadAssets`

## Supporto

tech@mlabfactory.it
