<?php

/* TVI
 * Copyright (C) 2015	Florian HENRY 		<florian.henry@open-concept.pro>
 * Copyright (C) 2020   Maxime DEMAREST     <maxime@indelog.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file htdocs/loan/calcmens.php
 *  \ingroup    loan
 *  \brief File to calculate loan monthly payments
 */

defineIfNotDefined('NOTOKENRENEWAL', '1'); // Disables token renewal
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require BASE_PATH . '/../Dolibarr/Lib/Loan.php';

$mens = price2num(GETPOST('mens'));
$capital = price2num(GETPOST('capital'));
$rate = price2num(GETPOST('rate'));
$echance = GETPOSTINT('echeance');
$nbterm = GETPOSTINT('nbterm');

top_httphead();

$output = array();

$output = loanCalcMonthlyPayment($mens, $capital, $rate, $echance, $nbterm);

echo json_encode($output);
