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

import type { Node, View } from '@nextcloud/files'

import { Permission, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { mdiFilePdfBox } from '@mdi/js'

// import Vue from 'vue'
import Vue from 'vue'
declare module "*.vue" {
	interface VueConstructor {
		// You can add properties or methods here if needed
	}
}
import Merge from './Merge.vue'
import Split from './Split.vue'

type PdfActionContext = {
	nodes: Node[]
	view: View
	folder?: {
		path?: string
	}
	contents?: Node[]
}

const getNodes = (context: PdfActionContext | Node[]): Node[] => Array.isArray(context) ? context : context.nodes

const getSingleNode = (context: PdfActionContext | Node[] | Node): Node => {
	if (Array.isArray(context)) {
		return context[0]
	}

	return 'nodes' in context ? context.nodes[0] : context
}

const isPdfNode = (node: Node): boolean => node.mime === 'application/pdf' || node.extension === '.pdf'

export const pdfAction = new FileAction({
	id: 'pdfmerge',
	displayName: (context: PdfActionContext | Node[], view?: View) => {
		const files = getNodes(context)
		return files.length > 1 ? t('pdftool', 'Merge PDF\'s') : t('pdftool', 'Split PDF')
	},
	title: (context: PdfActionContext | Node[], view?: View) => {
		const files = getNodes(context)
		return files.length > 1 ? t('pdftool', 'Merge PDF\'s') : t('pdftool', 'Split PDF')
	},
	iconSvgInline: () => `<svg viewBox="0 0 24 24"><path d="${mdiFilePdfBox}" /></svg>`,
	enabled(context: PdfActionContext | Node[]) {
		const nodes = getNodes(context)
		if (nodes.length === 0) {
			return false
		}

		const dirname = nodes[0].dirname
		return nodes.every(node =>
			(node.permissions & Permission.DELETE) !== 0
			&& isPdfNode(node)
			&& node.dirname === dirname)
	},

	async exec(context: PdfActionContext | Node, view?: View, dir?: string): Promise<boolean | null> {
		const file = getSingleNode(context)
		return new Promise(resolve => {
			try {
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

	async execBatch(context: PdfActionContext | Node[], view?: View): Promise<(boolean | null)[]> {
		const files = getNodes(context)
		if (files.length === 1) {
			const success = await this.exec(files[0], view)
			return [success]
		}

		return new Promise(resolve => {
			try {
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
								resolve(files.map(() => success))
							},
							closed: () => {
								vueInstance.$destroy()
								if (vueInstance.$el) {
									vueInstance.$el.innerHTML = ''
								}
								resolve(files.map(() => null))
							},
						},
					}),
				})
			} catch (error) {
				// logger.error('Error while deleting a file', { error, source: file.source, node: file })
				resolve(files.map(() => false))
			}
		})
	},

	order: 100,
} as any)
