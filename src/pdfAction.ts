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
import Split from './Split.vue'

export const pdfAction = new FileAction({
	id: 'pdfmerge',
	displayName: (files: Node[], view: View) => files.length > 1 ? t('pdftool', 'Merge PDF\'s') : t('pdftool', 'Split PDF'),
	iconSvgInline: () => `<svg viewBox="0 0 24 24"><path d="${mdiFilePdfBox}" /></svg>`,
	enabled(nodes: Node[]) {
		window.console.info(nodes)
		const dirname = nodes[0].dirname
		return nodes.every(node =>
			(node.permissions & Permission.DELETE) !== 0
			&& node.extension === '.pdf'
			&& node.dirname === dirname)
	},

	async exec(file: Node, view: View, dir: string): Promise<boolean | null> {
		return new Promise(resolve => {
			try {
				console.info('PdfTool Multiselect action')
				Vue.mixin({ methods: { t } })

				const vueInstance = new Vue({
					el: '#pdftool-content',
					render: h => h(Split, {
						props: {
							file: file,
						},
						on: {
							processed: (success: boolean) => {
								vueInstance.$destroy()
								if (vueInstance.$el) {
									vueInstance.$el.innerHTML = ''
								}
								resolve(success)
							},
							closed: () => {
								vueInstance.$destroy()
								if (vueInstance.$el) {
									vueInstance.$el.innerHTML = ''
								}
								resolve(null)
							},
						},
					}),
				})
			} catch (error) {
				resolve(false)
			}
		})
	},

	async execBatch(files: Node[], view: View): Promise<(boolean | null)[]> {
		return new Promise(resolve => {
			try {
				console.info('PdfTool Multiselect action')
				Vue.mixin({ methods: { t } })

				const vueInstance = new Vue({
					el: '#pdftool-content',
					render: h => h(Merge, {
						props: {
							files: files,
						},
						on: {
							processed: (success: boolean) => {
								vueInstance.$destroy()
								if (vueInstance.$el) {
									vueInstance.$el.innerHTML = ''
								}
								resolve([success])
							},
							closed: () => {
								vueInstance.$destroy()
								if (vueInstance.$el) {
									vueInstance.$el.innerHTML = ''
								}
								resolve([null])
							},
						},
					}),
				})
			} catch (error) {
				// logger.error('Error while deleting a file', { error, source: file.source, node: file })
				resolve([false])
			}
		})
	},

	order: 100,
})
