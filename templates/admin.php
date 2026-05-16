<?php

/** @var \OCP\IL10N $l */
/** @var array $_ */

script('pdftool', 'pdftool-admin');
style('pdftool', 'pdftool-admin');
?>
<div id="pdftool-admin" class="section">
	<h2><?php p($l->t('PDF Tool')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Here you can configure the PDF Tool app.')); ?></p>

	<div class="pdftool-admin-group">
		<h3><?php p($l->t('Processing engine')); ?></h3>
		<div id="pdftool_engine_setting">
			<input type="radio" name="pdftool_engine" id="pdftool_engine_gs" value="gs"
				<?php if ($_['pdfToolEngine'] === 'gs') {
					p('checked');
				}
				?>
				<?php if (!$_['gsIsAvailable']) {
					p('disabled');
				}
				?>>
			<label for="pdftool_engine_gs"><?php p($l->t('Use ghostscript')); ?></label>
			<?php if (!$_['gsIsAvailable']) { ?>
				<p class="settings-hint warning"><?php p($l->t('Ghostscript is not available. Please install it and configure the path in your environment.')); ?></p>
			<?php } ?>
			<br>
			<input type="radio" name="pdftool_engine" id="pdftool_engine_tcpdf" value="tcpdf"
				<?php if ($_['pdfToolEngine'] === 'tcpdf') {
					p('checked');
				}
				?>
				<?php if (!$_['gsIsAvailable']) {
					p('checked');
				}
				?>>
			<label for="pdftool_engine_tcpdf"><?php p($l->t('Use tcpdf')); ?></label>
		</div>
	</div>

	<div class="pdftool-admin-group">
		<h3><?php p($l->t('Limits')); ?></h3>
		<div>
			<label for="pdftool_max_pages"><?php p($l->t('PDF max page count')); ?></label>
			<input type="number" id="pdftool_max_pages" min="1" value="<?php p($_['pdfToolMaxPages']); ?>">
		</div>
		<div>
			<label for="pdftool_max_pdfs"><?php p($l->t('Max number of PDF\'s for batch processing')); ?></label>
			<input type="number" id="pdftool_max_pdfs" min="1" value="<?php p($_['pdfToolMaxPdfs']); ?>">
		</div>
	</div>
</div>
