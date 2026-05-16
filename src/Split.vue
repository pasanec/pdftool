<!--
 @copyright Copyright (c) 2025 Immanuel Pasanec <i@pasanec.de>

 @author Immanuel Pasanec <i@pasanec.de>

 @license GNU AGPL version 3 or any later version

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as
 published by the Free Software Foundation, either version 3 of the
 License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div id="pdftool-content" class="app-pdftool">
		<NcModal
			v-if="modal"
			@close="closeModal"
			class="pdftool-modal"
			size="normal"
						:name="t('pdftool', 'Split PDF\'s')"
			:outTransition="true">
						<h2>{{ t('pdftool', 'Split PDF\'s') }}</h2>
			<div class="pdftool-filename">
				<label for="filename">{{ t('pdftool', 'Output folder') }}</label>
				<input
					v-model="filename"
					id="filename"
					required
					:aria-invalid="folderNameInvalid ? 'true' : 'false'" />
				<div v-if="folderNameInvalid" class="filename-warning">
					{{ t('pdftool', 'Output folder is required.') }}
				</div>
			</div>
			<div class="desk">
   			<div class="document" v-for="(pageNumber, id) in pageNumbers" :key="id">
				<div class="mime-pdf"></div>
				<div class="filename" v-if="warnId !== id" >{{ t('pdftool', 'Page split point') }}</div>
				<div class="pagewarning filename" v-if="warnId === id" >{{ warnMessage }}</div>
				<input type="number" :value="pageNumbers[id]" @change="updatePageNumber(id, $event.target.value)" min="1" class="page-number-input" />
				<div class="filename pagenum">/ {{pageNumber + 1}}</div>
				<NcButton
					@click="removePageNumber(id)"
					:aria-label="t('pdftool', 'Remove split point.')"
					:disabled="false"
					:readonly="false"
					:size="'small'"
					variant="tertiary">
					&times;
				</NcButton>
			</div>
			<div class="add-button-container">
				<NcButton
					@click="addPageNumber"
					:aria-label="t('pdftool', 'Add split point.')"
					:disabled="warnId === null ? false : true"
					:size="'normal'"
					variant="tertiary">
				<Plus :size="20" />
		</NcButton>
			</div>
			</div>
			<div class="buttons">
				<NcButton
					@click="split"
					:disabled="!canSplit"
					:readonly="false"
					type="primary">
										<template>{{ t('pdftool', 'Split') }}</template>
				</NcButton>
				<NcButton
					@click="closeModal"
					:disabled="false"
					:readonly="false"
					type="primary">
					<template>{{ t('pdftool', 'Cancel') }}</template>
				</NcButton>
			</div>
		</NcModal>
				<NcModal v-if="error" @close="closeModal" class="pdftool-modal" size="normal" :name="t('pdftool', 'Error')" :outTransition="true">
			<div class="modal-error"><h2>{{ t('pdftool', 'An error has occurred.') }}</h2></div>
			<div class="buttons">
				<NcButton
					@click="closeModal"
					:disabled="false"
					:readonly="false"
					type="primary">
					<template>{{ t('pdftool', 'OK') }}</template>
				</NcButton>
			</div>
		</NcModal>
		<NcModal v-if="splitting" :show-close="false" class="pdftool-modal" size="normal">
			<div class="loading-container">
				<NcLoadingIcon :size="64" appearance="dark" />
				<p>{{ t('pdftool', 'Splitting...') }}</p>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { NcActionButton, NcAppContent, NcAppNavigation, NcAppNavigationItem, NcAppNavigationNew, NcButton, NcModal} from '@nextcloud/vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import Plus from 'vue-material-design-icons/Plus.vue'
// import draggable from 'vuedraggable' // Removed draggable

// import '@nextcloud/dialogs/styles/toast.scss'
import '@nextcloud/dialogs/style.css'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'


export default {
	name: 'Split',
	components: {
		NcActionButton,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcButton,
		NcModal,
		NcLoadingIcon,
		Plus,
	},
	data() {
		return {
			modal: true,
			error: false,
			splitting: false,
			updating: false,
			loading: true,
			filename: '',
			pageNumbers: {},
			nextId: 1,
			warnId: null,
			warnMessage: '',
			pageCount: 0,
		}
	},
	props: {
		file: {},
	},
	computed: {
		folderNameInvalid() {
			return this.filename.trim() === ''
		},
		canSplit() {
			return !this.folderNameInvalid && Object.keys(this.pageNumbers).length > 0 && this.warnId === null
		},
	},
	async mounted() {
		this.file= this.file
		this.filename = this.file.basename.substring(0, this.file.basename.length - 4) + '-' + t('pdftool', 'split') + '/'
		axios.get(generateUrl('/apps/pdftool/pagecount/' + this.file.fileid)).then((response) => {
			this.pageCount = response.data
			this.loading = false
		}).catch((error) => {
			console.error(error)
			showError(t('pdftool', 'Could not retrieve page count.'))
			this.error = true
			this.loading = false
		})
	},

	methods: {
		getParentDirname(node) {
			if (node.dirname) {
				return node.dirname
			}

			const filename = node.attributes?.filename
			if (!filename) {
				return '/'
			}

			const root = node.root || filename.match(/^\/files\/[^/]+/)?.[0] || ''
			const relativePath = root !== '' && filename.startsWith(root)
				? filename.substring(root.length)
				: filename
			const index = relativePath.lastIndexOf('/')

			return index > 0 ? relativePath.substring(0, index) : '/'
		},
		async refreshFolder(node) {
			const dirname = this.getParentDirname(node)
			const path = dirname === '/' ? davRootPath : `${davRootPath}${dirname}`
			const client = davGetClient()
			const result = await client.stat(path, {
				details: true,
				data: davGetDefaultPropfind(),
			})
			const updatedNode = davResultToNode(result.data)
			if (updatedNode.fileid) {
				emit('files:node:updated', updatedNode)
			}
		},
		updatePageNumber(id, newValue) {
			let value
			if (Number(newValue) >= this.pageCount) {
				value = 1
			} else if (Number(newValue) < 1) {
				value = this.pageCount - 1
			} else {
				value = Number(newValue)
			}
			
			this.warnId = null

			if (isNaN(value) || !Number.isInteger(value) || value < 1) {
				this.warnMessage = t('pdftool', 'Page number must be a positive integer.')
				this.warnId = null
				this.$nextTick(() => {
					this.warnId = id
				})
				setTimeout(() => {
					this.warnId = null
				}, 3000)
				return
			}

			const isDuplicate = Object.entries(this.pageNumbers).some(([key, val]) => key !== id && val === value)
			if (isDuplicate) {
				this.warnMessage = t('pdftool', 'Duplicate numbers not allowed!')
				this.warnId = null
				this.$nextTick(() => {
					this.warnId = id
				})
			}

			this.$set(this.pageNumbers, id, value)
		},
		async split() { // Renamed from merge
			if (!this.canSplit) {
				return
			}
			this.modal = false
			this.splitting = true
			const data = {
				file: this.file,
				pageNumbers: this.pageNumbers,
				outputFolder: this.filename.trim(),
			}
			try {
				const response = await axios.post(generateUrl('/apps/pdftool/split'), data)
				await this.refreshFolder(this.file)
				this.splitting = false
				this.$emit('processed', true)
			} catch (e) {
				console.error(e)
				showError(this.getErrorMessage(e, t('pdftool', 'Could not split PDF.')))
				this.splitting = false
				this.$emit('processed', false)
			}
		},
		getErrorMessage(error, fallback) {
			return error.response?.data?.message || fallback
		},
		addPageNumber() {
			if (this.pageNumbers.length === this.pageCount - 1) {
				showError(t('pdftool', 'There are no more pages to split.')) // Changed error message
				return
			}
			const newId = this.nextId++
			const values = Object.values(this.pageNumbers)
			const newValue = values.length > 0 ? Math.max(0, ...values.filter(v => Number.isInteger(v))) + 1 : 1
			this.$set(this.pageNumbers, newId, newValue)
			window.console.log(this.pageNumbers)
		},
		removePageNumber(id) { // New method to remove a page number
			this.$delete(this.pageNumbers, id)
		},
		closeModal() {
			this.modal = false
			this.splitting = false // Renamed from merging
			this.$emit('closed')
		},
	},
}
</script>
<style scoped lang="scss">
	.pdftool-modal {
		h2 {
			text-align: center;
			margin-top: 20px;
		}
		.pdftool-filename {
			width: 100%;
			max-width: 500px;
			margin: auto;
			padding: 4px 0;
			#filename {
				width: 80%;
				margin-left: 4px;
			}
			.filename-warning {
				color: var(--color-error);
				margin-left: calc(20% + 4px);
				padding-top: 4px;
			}
		}
		.desk {
			width: 100%;
			max-width: 500px;
			height: fit-content;
			border: 2px solid gray;
			border-radius: 5px;
			margin: 20px auto 20px auto;
			padding: 4px 0;
			background: lightgray;
			.add-button-container {
				display: flex;
				justify-content: right;
				padding: 10px 0;
				.button-vue {
					margin-right: 10px;
				}
			}
			.document {
				width: calc(100% - 25px);
				border: 2px solid gray;
				border-radius: 4px;
				margin: 4px auto;
				padding: 0 5px;
				font-weight: 600;
				background: lightskyblue;
				display: flex;
				align-items: center;
				.filename {
					height: 2em;
					line-height: 2em;
					vertical-align: middle;
					display: inline-block;
					flex-grow: 1;
					padding-left: 10px;
				}
				.pagewarning {
					color: red;
					font-weight: bold;
				}
				.filename.pagenum {
					flex-grow: 0.04;
					padding-left: 3px;
				}
				.page-number-input {
					width: 60px;
				}
			}
		}
		.modal-error {
			display: flex;
			justify-content: space-around;
		}
		.buttons {
			display: flex;
			justify-content: space-evenly;
			width: 60%;
			margin: auto;
			padding-bottom: 20px;
			button {
				height: 1em;
			}
		}

	}

	.loading-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 20px;
		p {
			margin-top: 10px;
		}
	}

	input[type='text'] {
		width: 100%;
	}

	textarea {
		flex-grow: 1;
		width: 100%;
	}
	.mime-pdf {
		background-image: url(/img/application-pdf.svg);
		height: 2em;
		width: 2em;
		background-size: contain;
		background-repeat: no-repeat;
		display: inline-block;
		vertical-align: middle;
	}
</style>
