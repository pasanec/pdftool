(function (OC, OCA) {
	'use strict';

	$(document).ready(function () {
		var adminUrl = OC.generateUrl('/apps/pdftool/settings/admin');

		$('#pdftool_engine_setting input[type="radio"]').change(function () {
			var engine = $(this).val();
			OC.AppConfig.setValue('pdftool', 'pdf_tool_engine', engine);
		});

		$('#pdftool_max_pages').change(function () {
			var maxPages = $(this).val();
			OC.AppConfig.setValue('pdftool', 'pdf_tool_max_pages', maxPages);
		});

		$('#pdftool_max_pdfs').change(function () {
			var maxPdfs = $(this).val();
			OC.AppConfig.setValue('pdftool', 'pdf_tool_max_pdfs', maxPdfs);
		});
	});

})(OC, OCA);
