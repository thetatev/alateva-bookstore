<?php
// config/app.php - ընդհանուր կարգավորումներ (կարելի է փոխել միջավայրի փոփոխականներով)
// APP_ENV=production  -> էլ. նամակների տեքստը լոգում չի պահվում
// APP_URL=https://example.com/alateva  -> հղումների հիմքը նամակներում (production-ում պարտադիր սահմանեք)
if (!defined('APP_ENV'))   { define('APP_ENV', getenv('APP_ENV') ?: 'development'); }
if (!defined('APP_URL'))   { define('APP_URL', rtrim(getenv('APP_URL') ?: '', '/')); }
if (!defined('SHOP_NAME')) { define('SHOP_NAME', 'Alateva'); }
// Այս հասցեին գալիս են նոր պատվերների ծանուցումները
if (!defined('SHOP_EMAIL')) { define('SHOP_EMAIL', getenv('SHOP_EMAIL') ?: 'info@alateva.com'); }
