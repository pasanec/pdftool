import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

document.addEventListener('DOMContentLoaded', () => {
	const adminUrl = generateUrl('/apps/pdftool/settings/admin')
	const engineInputs = Array.from(document.querySelectorAll('#pdftool_engine_setting input[type="radio"]'))
	const maxPagesInput = document.querySelector('#pdftool_max_pages')
	const maxPdfsInput = document.querySelector('#pdftool_max_pdfs')

	if (maxPagesInput === null || maxPdfsInput === null) {
		return
	}

	const saveSettings = async () => {
		const checkedEngine = engineInputs.find(input => input.checked)

		try {
			await axios.post(adminUrl, {
				engine: checkedEngine?.value,
				maxPages: Number(maxPagesInput.value),
				maxPdfs: Number(maxPdfsInput.value),
			})
			showSuccess(t('pdftool', 'PDF Tool settings saved.'))
		} catch (error) {
			showError(error.response?.data?.message || t('pdftool', 'Could not save PDF Tool settings.'))
		}
	}

	engineInputs.forEach(input => input.addEventListener('change', saveSettings))
	maxPagesInput.addEventListener('change', saveSettings)
	maxPdfsInput.addEventListener('change', saveSettings)
})
