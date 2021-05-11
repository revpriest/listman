<template>
	<div id="content" class="app-listman">
		<AppNavigation>
			<AppNavigationNew v-if="!loading"
				:text="t('listman', 'New list')"
				:disabled="false"
				button-id="new-listman-button"
				button-class="icon-add"
				@click="newList" />
			<ul>
				<AppNavigationItem v-for="list in lists"
					:key="list.id"
					:title="list.title ? list.title : t('listman', 'New list')"
					:class="{active: currentListId === list.id}"
					@click="openList(list)">
					<template slot="actions">
						<ActionButton v-if="list.id === -1"
							icon="icon-close"
							@click="cancelNewList(list)">
							{{ t('listman', 'Cancel list creation') }}
						</ActionButton>
						<ActionButton v-else
							icon="icon-delete"
							@click="deleteList(list)">
							{{ t('listman', 'Delete list') }}
						</ActionButton>
					</template>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<div v-if="currentList">
				<input ref="title"
					v-model="currentList.title"
					type="text"
					class="listman_listTitle"
					:disabled="updating">
				<textarea ref="desc"
					v-model="currentList.desc"
					class="listman_listDesc"
					:disabled="updating" />
				<input type="button"
					class="primary"
					:value="t('listman', 'New Member')"
					:disabled="updating"
					@click="newMember">
				<input type="button"
					class="primary"
					:value="t('listman', 'Save')"
					:disabled="updating || !savePossible"
					@click="saveList">
				<ul class="listman_members">
					<li v-for="member in currentListMembers"
						:key="member.id"
						:title="member.name ? member.name : t('listman', 'New Member')"
						@click="openMember(member)">
						<input ref="name"
							v-model="member.name"
							type="text"
							class="listman_memberName"
							:disabled="updating">
						<input ref="email"
							v-model="member.email"
							type="text"
							class="listman_memberEmail"
							:disabled="updating">
						<input type="button"
							class="primary"
							:value="t('listman', 'Save')"
							:disabled="updating || !savePossible"
							@click="saveMember(member)">
						<div class="listman_memberactions">
							<ActionButton v-if="member.id === -1"
								icon="icon-close"
								class="listman_memberaction"
								@click="cancelNewMember(member)">
								{{ t('listman', '') }}
							</ActionButton>
							<ActionButton v-else
								icon="icon-delete"
								class="listman_memberaction"
								@click="deleteMember(member)">
								{{ t('listman', '') }}
							</ActionButton>
						</div>
					</li>
				</ul>
			</div>
			<div v-else id="emptydesc">
				<div class="icon-file" />
				<p class="listman_empty">
					{{ t('listman', 'Select or create a list from the menu on the left') }}
				</p>
			</div>
		</AppContent>
	</div>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'

import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

export default {
	name: 'App',
	components: {
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
	},
	data() {
		return {
			lists: [],
			currentListId: null,
			currentListMembers: [],
			updating: false,
			loading: true,
		}
	},
	computed: {
		/**
		 * Return the currently selected list object
		 * @returns {Object|null}
		 */
		currentList() {
			if (this.currentListId === null) {
				return null
			}
			return this.lists.find((list) => list.id === this.currentListId)
		},

		/**
		 * Returns true if a list is selected and its title is not empty
		 * @returns {Boolean}
		 */
		savePossible() {
			return this.currentList && this.currentList.title !== ''
		},
	},
	/**
	 * Fetch list of lists when the component is loaded
	 */
	async mounted() {
		try {
			console.error('Fetching from url ', generateUrl('/apps/listman/lists'))
			const response = await axios.get(generateUrl('/apps/listman/lists'))
			console.error('got reply', response, response.data)
			this.lists = response.data
		} catch (e) {
			console.error(e)
			showError(t('listman', 'Could not fetch lists'))
		}
		this.loading = false
	},

	methods: {
		/**
		 * Create a new list and focus the list desc field automatically
		 * @param {Object} list List object
		 */
		async openList(list) {
			console.error('Opening List')
			if (this.updating) {
				return
			}
			this.currentListId = list.id
			this.$nextTick(() => {
				this.$refs.desc.focus()
			})

			// Fill members-list
			const url = generateUrl('/apps/listman/listmembers/' + this.currentListId)
			console.error('Fetching ' + url)
			const response = await axios.get(url)
			console.error('got reply', response)
			this.currentListMembers = response.data
		},
		/**
		 * Action tiggered when clicking the save button
		 * create a new list or save
		 */
		saveList() {
			if (this.currentListId === -1) {
				this.createList(this.currentList)
			} else {
				this.updateList(this.currentList)
			}
		},
		/**
		 * Create a new list and focus the list desc field automatically
		 * The list is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		newList() {
			if (this.currentListId !== -1) {
				this.currentListId = -1
				this.lists.push({
					id: -1,
					title: '',
					desc: '',
				})
				this.$nextTick(() => {
					this.$refs.title.focus()
				})
			}
		},
		/**
		 * Abort creating a new list
		 */
		cancelNewList() {
			this.lists.splice(this.lists.findIndex((list) => list.id === -1), 1)
			this.currentListId = null
		},
		/**
		 * Create a new list by sending the information to the server
		 * @param {Object} list List object
		 */
		async createList(list) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/listman/lists'), list)
				const index = this.lists.findIndex((match) => match.id === this.currentListId)
				this.$set(this.lists, index, response.data)
				this.currentListId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not create the list'))
			}
			this.updating = false
		},
		/**
		 * Update an existing list on the server
		 * @param {Object} list List object
		 */
		async updateList(list) {
			this.updating = true
			try {
				await axios.put(generateUrl(`/apps/listman/lists/${list.id}`), list)
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not update the list'))
			}
			this.updating = false
		},
		/**
		 * Delete a list, remove it from the frontend and show a hint
		 * @param {Object} list List object
		 */
		async deleteList(list) {
			try {
				await axios.delete(generateUrl(`/apps/listman/lists/${list.id}`))
				this.lists.splice(this.lists.indexOf(list), 1)
				if (this.currentListId === list.id) {
					this.currentListId = null
				}
				showSuccess(t('listman', 'List deleted'))
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not delete the list'))
			}
		},
		/**
		 * Delete a member, remove it from the frontend and show a hint
		 * @param {Object} member Member object
		 */
		async deleteMember(member) {
			try {
				const url = generateUrl(`/apps/listman/members/${member.id}`)
				console.error('opening url to delete member:' + url)
				await axios.delete(url)
				this.currentListMembers.splice(this.currentListMembers.indexOf(member), 1)
				showSuccess(t('listman', 'Member deleted'))
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not delete the member'))
			}
		},
		/**
		 * Abort creating a new member
		 */
		cancelNewMember() {
			this.currentListMembers.splice(this.currentListMembers.findIndex((member) => member.id === -1), 1)
		},
		/**
		 * Action tiggered when clicking the save button
		 * create a new member or save.
		 * @param {Object} member Member object
		 */
		saveMember(member) {
			if (member.id === -1) {
				this.createMember(member)
			} else {
				this.updateMember(member)
			}
		},
		/**
		 * Create a new member by sending the information to the server
		 * @param {Object} member Member object
		 */
		async createMember(member) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/listman/members'), member)
				const index = this.currentListMembers.findIndex((match) => match.id === member.id)
				this.$set(this.currentListMembers, index, response.data)
				member.id = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not create the member'))
			}
			this.updating = false
		},
		/**
		 * Update an existing member on the server
		 * @param {Object} member Member object
		 */
		async updateMember(member) {
			this.updating = true
			try {
				await axios.put(generateUrl(`/apps/listman/members/${member.id}`), member)
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not update the member'))
			}
			this.updating = false
		},
		/**
		 * Create a new member
		 * The member is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		newMember() {
			if ((this.currentListId !== -1) && (this.currentListMembers != null)) {
			  this.currentListMembers.push({
					id: -1,
					name: '',
					email: '',
				})
				this.$nextTick(() => {
					this.$refs.name.focus()
				})
			}
		},
		/**
		 * Create a new list and focus the list desc field automatically
		 * @param {Object} member Member object
		 */
		async openMember(member) {
			if (this.updating) {
				return
			}
			console.error('Opening Member', member)
		},
	},
}
</script>
