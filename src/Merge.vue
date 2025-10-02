<!--
 @copyright Copyright (c) 2023 Immanuel Pasanec <i@pasanec.de>

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
						:name="t('pdftool', 'Merge PDF\'s')"
			:outTransition="true">
						<h2>{{ t('pdftool', 'Merge PDF\'s') }}</h2>
			<div class="pdftool-filename">
				<label for="filename">{{ t('pdftool', 'Output file') }}</label>
				<input v-model="filename" id="filename"/>
			</div>
			<draggable class="desk" v-model="fileList" group="files" @start="drag=true" @end="drag=false">
   			<div class="document" v-for="element in fileList" :key="element.id">
				<div class="mime-pdf"></div>
				<div class="filename">{{element.displayname}}</div>
			</div>
			</draggable>
			<div class="buttons">
				<NcButton
					@click="merge"
					:disabled="false"
					:readonly="false"
					type="primary">
										<template>{{ t('pdftool', 'Merge') }}</template>
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
		<NcModal v-if="merging" :show-close="false" class="pdftool-modal" size="normal">
			<div class="loading-container">
				<NcLoadingIcon :size="64" appearance="dark" />
				<p>{{ t('pdftool', 'Merging...') }}</p>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { NcActionButton, NcAppContent, NcAppNavigation, NcAppNavigationItem, NcAppNavigationNew, NcButton, NcModal} from '@nextcloud/vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import draggable from 'vuedraggable'

// import '@nextcloud/dialogs/styles/toast.scss'
import '@nextcloud/dialogs/style.css'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'


export default {
	name: 'Merge',
	components: {
		NcActionButton,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcButton,
		NcModal,
		draggable,
		NcLoadingIcon,
	},
	data() {
		return {
			notes: [],
			modal: true,
			error: false,
			merging: false,
			currentNoteId: null,
			updating: false,
			loading: true,
			fileList: [],
			filename: '',
		}
	},
	props: {
		files: [],
	},
	computed: {
	},
	/**
	 * Fetch list of notes when the component is loaded
	 */
	async mounted() {
		this.fileList = this.files
		this.filename = this.files[0].basename.substring(0, this.files[0].basename.length - 4) + '-' + t('pdftool', 'merged') + '.pdf'
	},

	methods: {
		async merge() {
			this.modal = false
			this.merging = true
			const data = {
				fileList: this.fileList,
				outputFile: this.filename,
			}
			try {
				const dirname = this.fileList[0].dirname
				const response = await axios.post(generateUrl('/apps/pdftool/merge'), data)
				const client = davGetClient()
				client.stat(`${davRootPath}${dirname}`, {
					details: true,
					data: davGetDefaultPropfind(),
				}).then((result) => {
						const node = davResultToNode(result.data)
						emit('files:node:updated', node)
					})
				this.merging = false
				this.$emit('processed', true)
			} catch (e) {
				console.error(e)
				showError(t('pdftool', 'Could not merge PDF.'))
				this.merging = false
				this.$emit('processed', false)
			}
		},
		/**
		 * Create a new note and focus the note content field automatically
		 * @param {Object} note Note object
		 */
		openNote(note) {
			if (this.updating) {
				return
			}
			this.currentNoteId = note.id
			this.$nextTick(() => {
				this.$refs.content.focus()
			})
		},
		/**
		 * Action tiggered when clicking the save button
		 * create a new note or save
		 */
		saveNote() {
			if (this.currentNoteId === -1) {
				this.createNote(this.currentNote)
			} else {
				this.updateNote(this.currentNote)
			}
		},
		/**
		 * Create a new note and focus the note content field automatically
		 * The note is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		newNote() {
			if (this.currentNoteId !== -1) {
				this.currentNoteId = -1
				this.notes.push({
					id: -1,
					title: '',
					content: '',
				})
				this.$nextTick(() => {
					this.$refs.title.focus()
				})
			}
		},
		/**
		 * Abort creating a new note
		 */
		cancelNewNote() {
			this.notes.splice(this.notes.findIndex((note) => note.id === -1), 1)
			this.currentNoteId = null
		},
		/**
		 * Create a new note by sending the information to the server
		 * @param {Object} note Note object
		 */
		async createNote(note) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/pdftool/notes'), note)
				const index = this.notes.findIndex((match) => match.id === this.currentNoteId)
				this.$set(this.notes, index, response.data)
				this.currentNoteId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('pdftool', 'Could not create the note'))
			}
			this.updating = false
		},
		/**
		 * Update an existing note on the server
		 * @param {Object} note Note object
		 */
		async updateNote(note) {
			this.updating = true
			try {
				await axios.put(generateUrl(`/apps/pdftool/notes/${note.id}`), note)
			} catch (e) {
				console.error(e)
				showError(t('pdftool', 'Could not update the note'))
			}
			this.updating = false
		},
		/**
		 * Delete a note, remove it from the frontend and show a hint
		 * @param {Object} note Note object
		 */
		async deleteNote(note) {
			try {
				await axios.delete(generateUrl(`/apps/pdftool/notes/${note.id}`))
				this.notes.splice(this.notes.indexOf(note), 1)
				if (this.currentNoteId === note.id) {
					this.currentNoteId = null
				}
				showSuccess(t('pdftool', 'Note deleted'))
			} catch (e) {
				console.error(e)
				showError(t('pdftool', 'Could not delete the note'))
			}
		},
		closeModal() {
			this.modal = false
			this.merging = false
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
			.document {
				width: calc(100% - 25px);
				border: 2px solid gray;
				border-radius: 4px;
				margin: 4px auto;
				padding: 0 5px;
				font-weight: 600;
				background: lightskyblue;
				cursor: grab;
				.filename {
					height: 2em;
					line-height: 2em;
					vertical-align: middle;
					display: inline-block;
				}
			}
			.document.sortable-chosen {
				cursor: grabbing;
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
