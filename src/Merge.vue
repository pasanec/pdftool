<template>
	<div id="pdftools-content" class="app-pdftool">
		<Modal
			v-if="modal"
			@close="closeModal"
			class="pdftool-modal"
			size="normal"
			:outTransition="true">
			<h2>Merge PDFs</h2>
			<draggable class="desk" v-model="files" group="files" @start="drag=true" @end="drag=false">
   			<div class="document" v-for="element in files" :key="element.id">{{element.name}}</div>
			</draggable>
			<div class="buttons">
				<Button
					:disabled="false"
					:readonly="false"
					type="primary">
					<template>Merge</template>
				</Button>
				<Button
					@click="closeModal"
					:disabled="false"
					:readonly="false"
					type="primary">
					<template>Cancel</template>
				</Button>
			</div>

		</Modal>
	</div>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import draggable from 'vuedraggable'
import Button from '@nextcloud/vue/dist/Components/Button'
import Modal from '@nextcloud/vue/dist/Components/Modal'

import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

export default {
	name: 'Merge',
	components: {
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		draggable,
		Button,
		Modal,
	},
	data() {
		return {
			notes: [],
			modal: true,
			currentNoteId: null,
			updating: false,
			loading: true,
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
	},

	methods: {
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
			}
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

	input[type='text'] {
		width: 100%;
	}

	textarea {
		flex-grow: 1;
		width: 100%;
	}
</style>
