<div class="curated-tour-special-header clearfix">
	<div class="curated-tour-special-header-content">
		<h1 class="curated-tour-special-header-content-title">
			<?= wfMessage( 'curated-tour-manage' )->escaped() ?>
		</h1>

		<p class="curated-tour-special-header-content-text">
			<?= wfMessage( 'curated-tour-header-text' )->parse() ?>
		</p>
	</div>
</div>
<?php if (!empty( $pageTour )): ?>
	<table class="article-table sortable curated-tour-special-list">
		<tr class="curated-tour-special-list-headers">
			<th class="curated-tour-special-list-header-name"><?= wfMessage( 'curated-tour-special-list-header-name' )->escaped() ?></th>
			<th class="curated-tour-special-list-header-selector"><?= wfMessage( 'curated-tour-special-list-header-selector' )->escaped() ?></th>
			<th class="curated-tour-special-list-header-notes"><?= wfMessage( 'curated-tour-special-list-header-notes' )->escaped() ?></th>
		</tr>
		<?php foreach ( $pageTour as $pageTourItem ): ?>
			<tr class="curated-tour-special-list-item">
				<td class="curated-tour-special-list-item-name"><?= $pageTourItem['PageName'] ?></td>
				<td class="curated-tour-special-list-item-selector"><?= $pageTourItem['Selector'] ?></td>
				<td class="curated-tour-special-list-item-notes"><?= $pageTourItem['Notes'] ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php else: ?>
	<div class="curated-tour-special-zero-status">
		<?=wfMessage( 'curatedTour-special-zero-state' )->parse();?>
	</div>
<?php endif; ?>
