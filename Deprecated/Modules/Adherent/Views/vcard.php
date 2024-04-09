<?php

$company = new Societe($db);
if ($object->socid) {
    $result = $company->fetch($object->socid);
}


// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr ' . DOL_VERSION);

$v->setUid('DOLIBARR-ADHERENTID-' . $object->id);
$v->setName($object->lastname, $object->firstname, "", $object->civility, "");
$v->setFormattedName($object->getFullName($langs, 1));

$v->setPhoneNumber($object->phone_pro, "TYPE=WORK;VOICE");
//$v->setPhoneNumber($object->phone_perso,"TYPE=HOME;VOICE");
$v->setPhoneNumber($object->phone_mobile, "TYPE=CELL;VOICE");
$v->setPhoneNumber($object->fax, "TYPE=WORK;FAX");

$country = $object->country_code ? $object->country : '';

$v->setAddress("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK;POSTAL");
// @phan-suppress-next-line PhanDeprecatedFunction  (setLabel is the old method, new is setAddress)
$v->setLabel("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK");

$v->setEmail($object->email);
$v->setNote($object->note_public);
$v->setTitle($object->poste);

// Data from linked company
if ($company->id) {
    $v->setURL($company->url, "TYPE=WORK");
    if (!$object->phone_pro) {
        $v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
    }
    if (!$object->fax) {
        $v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
    }
    if (!$object->zip) {
        $v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
    }
    // when company e-mail is empty, use only adherent e-mail
    if (empty(trim($company->email))) {
        // was set before, don't set twice
    } elseif (empty(trim($object->email))) {
        // when adherent e-mail is empty, use only company e-mail
        $v->setEmail($company->email);
    } else {
        $tmpobject = explode("@", trim($object->email));
        $tmpcompany = explode("@", trim($company->email));

        if (strtolower(end($tmpobject)) == strtolower(end($tmpcompany))) {
            // when e-mail domain of adherent and company are the same, use adherent e-mail at first (and company e-mail at second)
            $v->setEmail($object->email);

            // support by Microsoft Outlook (2019 and possible earlier)
            $v->setEmail($company->email, 'INTERNET');
        } else {
            // when e-mail of adherent and company complete different use company e-mail at first (and adherent e-mail at second)
            $v->setEmail($company->email);

            // support by Microsoft Outlook (2019 and possible earlier)
            $v->setEmail($object->email, 'INTERNET');
        }
    }

    // Si adherent lie a un tiers non de type "particulier"
    if ($company->typent_code != 'TE_PRIVATE') {
        $v->setOrg($company->name);
    }
}

// Personal information
$v->setPhoneNumber($object->phone_perso, "TYPE=HOME;VOICE");
if ($object->birth) {
    $v->setBirthday($object->birth);
}

$db->close();


// Renvoi la VCard au navigateur

$output = $v->getVCard();

$filename = trim(urldecode($v->getFileName())); // "Nom prenom.vcf"
$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
//$filename = dol_sanitizeFileName($filename);


header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Content-Length: " . dol_strlen($output));
header("Connection: close");
header("Content-Type: text/x-vcard; name=\"" . $filename . "\"");

print $output;
