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
				<input v-model="filename" id="filename"/>
			</div>
			<div class="desk">
   			<div class="document" v-for="(pageNumber, id) in pageNumbers" :key="id">
				<div class="mime-pdf"></div>
				<div class="filename">{{ t('pdftool', 'Page') }}</div>
				<div class="pagewarning" v-if="warnId === id" >{{ warnMessage }}</div>
				<input type="number" :value="pageNumbers[id]" @change="updatePageNumber(id, $event.target.value)" min="1" class="page-number-input" />
				<div class="filename">/ {{pageNumber + 1}}</div>
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
					:disabled="false"
					:size="'normal'"
					variant="tertiary">
				<Plus :size="20" />
		</NcButton>
			</div>
			</div>
			<div class="buttons">
				<NcButton
					@click="split"
					:disabled="false"
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
	name: 'Split', // Changed from Merge
	components: {
		NcActionButton,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcButton,
		NcModal,
		// draggable, // Removed draggable
		NcLoadingIcon,
		Plus,
	},
	data() {
		return {
			modal: true,
			error: false,
			splitting: false, // Renamed from merging
			updating: false,
			loading: true,
			filename: '',
			pageNumbers: {}, // Added for page numbers
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
	},
	async mounted() {
		this.file= this.file
		this.filename = this.file.basename.substring(0, this.file.basename.length - 4) + '-' + t('pdftool', 'split') + '/'
		this.pageCount = axios.get(generateUrl('/apps/pdftool/pagecount/' + this.file.fileid)).then((response) => {
			this.pageCount = response.data.pageCount
			this.loading = false
		}).catch((error) => {
			console.error(error)
			showError(t('pdftool', 'Could not retrieve page count.'))
			this.error = true
			this.loading = false
		})
	},

	methods: {
		updatePageNumber(id, newValue) {
			if (Number(newValue) >= this.pageCount) {
				const value = 1
				return
			}
			if (Number(newValue) < 1) {
				const value = this.pageCount - 1
				return
			}
			const value = Number(newValue)

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
				setTimeout(() => {
					this.warnId = null
				}, 3000)
				return
			}

			this.$set(this.pageNumbers, id, value)
		},
		async split() { // Renamed from merge
			this.modal = false
			this.splitting = true
			const data = {
				file: this.file,
				pageNumbers: this.pageNumbers,
				outputFolder: this.filename,
			}
			try {
				const dirname = this.file.dirname
				const response = await axios.post(generateUrl('/apps/pdftool/split'), data) // Changed endpoint to /split
				const client = davGetClient()
				client.stat(`${davRootPath}${dirname}`, {
					details: true,
					data: davGetDefaultPropfind(),
				}).then((result) => {
						const node = davResultToNode(result.data)
						emit('files:node:updated', node)
					})
				this.splitting = false
				this.$emit('split', true) // Renamed event
			} catch (e) {
				console.error(e)
				showError(t('pdftool', 'Could not split PDF.')) // Changed error message
				this.splitting = false
				this.$emit('split', false) // Renamed event
			}
		},
		addPageNumber() {
			if (pageNumbers.length === this.pageCount - 1) {
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
				justify-content: center;
				padding: 10px 0;
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
