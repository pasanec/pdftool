/**
 * @copyright Copyright (c) 2023 Immanuel Pasanec <i@pasanec.de>
 *
 * @author Immanuel Pasanec <i@pasanec.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { Permission, Node, View, FileAction } from '@nextcloud/files'
import { showInfo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { mdiFilePdfBox } from '@mdi/js'
import { addNewFileMenuEntry, registerDavProperty, registerFileAction } from '@nextcloud/files'
import { displayName } from './pdfHelper'

// import Vue from 'vue'
import Vue from 'vue'
declare module "*.vue" {
	interface VueConstructor {
		// You can add properties or methods here if needed
	}
}
import Merge from './Merge.vue'

export const mergeAction = new FileAction({
	id: 'pdfmerge',
	displayName: (files: Node[], view: View) => 'Merge PDF\'s',
	iconSvgInline: () => `<svg viewBox="0 0 24 24"><path d="${mdiFilePdfBox}" /></svg>`,
	enabled(nodes: Node[]) {
		window.console.info(nodes)
		return nodes.length > 1 && nodes
			.every(node => (node.permissions & Permission.DELETE) !== 0 && node.extension === '.pdf')
	},

	async exec(file: Node, view: View, dir: string): Promise<boolean | null> {
		try {
			console.info('PdfTool Multiselect action')
			Vue.mixin({ methods: { t } })

			new Vue({
				el: '#pdftool-content',
				render: h => h(Merge, {
					props: {
						// files: selection,
						files: file,
						// TODO: look for replacement.
						//filelistObj: fileList,
					},
				}),
			})

			// // If trashbin is disabled, we need to ask for confirmation
			// if (!isTrashbinEnabled()) {
			// 	const confirm = await askConfirmation([file], view)
			// }
			//
			// // If the user cancels the deletion, we don't want to do anything
			// if (confirm === false) {
			// 	showInfo(t('files', 'Deletion cancelled'))
			// 	return null
			// }
			//
			// await deleteNode(file)
			//
			return true
		} catch (error) {
			//logger.error('Error while deleting a file', { error, source: file.source, node: file })
			return false
		}
	},

	async execBatch(files: Node[], view: View): Promise<(boolean | null)[]> {
		try {
			console.info('PdfTool Multiselect action')
			Vue.mixin({ methods: { t } })

			new Vue({
				el: '#pdftool-content',
				render: h => h(Merge, {
					props: {
						files: files,
					},
				}),
			})
			return [true]
		} catch (error) {
			//logger.error('Error while deleting a file', { error, source: file.source, node: file })
			return [false]
		}
	},

	order: 100,
})
