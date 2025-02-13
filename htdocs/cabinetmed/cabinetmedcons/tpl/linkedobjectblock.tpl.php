<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("orders");

$total=0; $ilink=0;

foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;
	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass.=' liste_sub_total';
	}
	?>
	<tr class="<?php echo $trclass; ?>">
		<td><?php echo $langs->trans("Consultation"); ?></td>
		<td><?php echo $objectlink->getNomUrl(1); ?></td>
		<td align="center"><?php echo $objectlink->ref_client; ?></td>
		<td align="center"><?php echo dol_print_date($objectlink->datecons, 'day'); ?></td>
		<td align="right"><?php
		if ($user->hasRight('cabinetmed', 'read')) {
			$amount_consultation = (int) $objectlink->montant_cheque + (int) $objectlink->montant_carte + (int) $objectlink->montant_espece + (int) $objectlink->montant_tiers;
			$total = $total + $amount_consultation;
			echo price($amount_consultation);
		} ?></td>
		<td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
		<td align="right">
			<?php
			// For now, shipments must stay linked to order, so link is not deletable
			if ($object->element != 'shipping') {
				?>
				<a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newtoken().'&dellinkid='.((int) $key); ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a>
				<?php
			}
			?>
		</td>
	</tr>
	<?php
}
if (count($linkedObjectBlock) > 1) {
	?>
	<tr class="liste_total <?php echo (empty($noMoreLinkedObjectBlockAfter)?'liste_sub_total':''); ?>">
		<td><?php echo $langs->trans("Total"); ?></td>
		<td></td>
		<td align="center"></td>
		<td align="center"></td>
		<td align="right"><?php echo price($total); ?></td>
		<td align="right"></td>
		<td align="right"></td>
	</tr>
	<?php
}
?>

<!-- END PHP TEMPLATE -->