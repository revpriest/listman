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
				<div id="selectview">
					<input type="button"
						class="primary"
						:value="t('listman', 'Show Details')"
						:disabled="updating || !savePossible"
						@click="showPane('details')">
					<input type="button"
						class="primary"
						:value="t('listman', 'Show Members')"
						:disabled="updating || !savePossible"
						@click="showPane('members')">
					<input type="button"
						class="primary"
						:value="t('listman', 'Show Messages')"
						:disabled="updating || !savePossible"
						@click="showPane('messages')">
					<input type="button"
						class="primary"
						:value="t('listman', 'Show Subscribe Form')"
						:disabled="updating || !savePossible"
						@click="showPane('form')">
				</div>
				<!-- Show Details Pane -->
				<div v-if="shownPane=='details'" id="shownPane">
					<h2>{{ currentList.title }} - {{ t('listman', 'List Details:') }}</h2>
					<input ref="title"
						v-model="currentList.title"
						type="text"
						placeholder="list title"
						class="listman_listTitle"
						:disabled="updating">
					<textarea ref="desc"
						v-model="currentList.desc"
						placeholder="description"
						class="listman_listDesc"
						:disabled="updating" />
					<input ref="redir"
						v-model="currentList.redir"
						placeholder="url to return to after un/subscribe confirmation (optional)"
						type="text"
						class="listman_listRedir"
						:disabled="updating">
					<input type="button"
						class="primary"
						:value="t('listman', 'Save List Details')"
						@click="saveList">
				</div>
				<!-- Show Member List -->
				<div v-if="shownPane=='members'" id="shownPane">
					<h2>{{ currentList.title }} - {{ t('listman', 'List Of Members') }}</h2>
					<ul class="listman_members">
						<li v-for="member in currentListMembers"
							:key="member.id"
							class="memberline">
							<div class="listman_memberlinecontent">
								<input ref="name"
									v-model="member.name"
									type="text"
									placeholder="Name"
									class="listman_memberName"
									:disabled="updating">
								<input ref="email"
									v-model="member.email"
									type="text"
									placeholder="Email"
									class="listman_memberEmail"
									:disabled="updating">
								<input ref="list_id"
									v-model="member.list_id"
									type="hidden"
									class="listman_memberListId"
									:disabled="updating">
								<select ref="state"
									v-model="member.state"
									type="text"
									class="listman_memberSelect"
									:disabled="updating">
									<option value="1" default>
										Subscribed
									</option>
									<option value="0">
										Unconfirmed
									</option>
									<option value="-1">
										Blocked
									</option>
								</select>
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
							</div>
						</li>
					</ul>
					<input type="button"
						class="primary"
						:value="t('listman', 'New Member')"
						:disabled="updating"
						@click="newMember">
				</div>
				<!-- Show Message List -->
				<div v-if="shownPane=='messages'" id="shownPane">
					<h2>{{ currentList.title }} - {{ t('listman', 'List Of Messages') }}</h2>
					<input
						id="listman_newmessagebutton"
						type="button"
						class="primary"
						:value="t('listman', 'New Message')"
						:disabled="updating"
						@click="newMessage">
					<ul class="listman_messages">
						<li v-for="message in currentListMessages"
							:key="message.id"
							class="messageline"
							@click="selectMessage(message)">
							<p class="listman_textline">
								{{ message.created_at }} - {{ message.subject }}
							</p>
							<div class="listman_messageactions">
								<ActionButton v-if="message.id === -1"
									icon="icon-close"
									class="listman_messageaction"
									@click="cancelNewMessage(message)" />
								<ActionButton v-else
									icon="icon-delete"
									class="listman_messageaction"
									@click="deleteMessage(message)" />
							</div>
							<div v-if="currentMessageId==message.id"
								id="listman_messagedetails">
								<p>{{ t('listman', 'Selected Message Details:') }}</p>
								<input ref="subject"
									v-model="message.subject"
									type="text"
									placeholder="subject"
									class="listman_listTitle"
									:disabled="updating">
								<textarea ref="body"
									v-model="message.body"
									placeholder="message body"
									class="composeTextarea"
									name="composeText" />
								<input
									id="listman_savemessage"
									type="button"
									class="primary"
									:value="t('listman', 'Save')"
									:disabled="updating || !savePossible"
									@click="saveMessage(message)">
								<span id="listman_numsent">
									<span
										id="listman_numsent_sent"
										:title="t('listman', 'sent')">
										{{ currentMessageSentDetails.sent }}
									</span> /
									<span
										id="listman_numsent_queued"
										:title="t('listman', 'queued')">
										{{ currentMessageSentDetails.queued }}
									</span> /
									<span
										id="listman_numsent_total"
										:title="t('listman', 'in-list')">
										{{ currentMessageSentDetails.total }}
									</span>
									{{ t('listman', 'sent') }}
								</span>
								<input
									id="listman_view"
									type="button"
									class="primary"
									:value="t('listman', 'Web View')"
									:disabled="updating || !savePossible"
									@click="webView(message)">
								<input
									id="listman_sendtoall"
									type="button"
									class="primary"
									:value="t('listman', 'Send To All')"
									:disabled="updating || !savePossible"
									@click="sendToAll(message)">
							</div>
						</li>
					</ul>
				</div>
				<!-- Show Subscribe Form -->
				<div v-if="shownPane=='form'" id="shownPane">
					<h2>{{ currentList.title }} - {{ t('listman', 'Subscribe-form code.') }}</h2>
					<pre id="subscribeFormText">{{ subscribeFormText }}</pre>
				</div>
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
			currentListRandId: null,
			currentListMembers: [],
			currentListMessages: [],
			currentMessageId: null,
			currentMessageSentDetails: {
				sent: '?',
				queued: '?',
				total: '?',
			},
			shownPane: 'details',
			updating: false,
			loading: true,
			subscribeFormText: null,
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
		 * Return the currently selected message object
		 * @returns {Object|null}
		 */
		currentMessage() {
			if (this.currentMessageId === null) {
				return null
			}
			return this.currentListMessages.find((message) => message.id === this.currentMessageId)
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
			const response = await axios.get(generateUrl('/apps/listman/lists'))
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
			const url = generateUrl('/apps/listman/listdetails/' + this.currentListId)
			const response = await axios.get(url)
			this.currentListMembers = response.data.members
			this.currentListMessages = response.data.messages
			this.currentListRandId = response.data.list.randid
			this.currentMessageId = null
			this.updateSubscribeFormText()
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
		* Action to signal the server to begin sending a
		* new email.
		* @param {Object} message Message object
		*/
		saveMessage(message) {
			if (message.id === -1) {
				this.createMessage(message)
			} else {
				this.updateMessage(message)
			}
		},
		/**
		* Open a new window/tab with the web-view of the message.
		* @param {Object} message Message object
		*/
		async webView(message) {
			if (message.id === -1) {
				alert('Save it first')
			} else {
				try {
					const url = generateUrl(`/apps/listman/message-view/${message.id}`)
					window.open(url, '_blank')
				} catch (e) {
					console.error(e)
					showError(t('listman', 'Could not signal to send the message') + e)
				}
			}
		},
		/**
		* Send the current message to everyone. That's basically
		* done on the server so we just send the server a message.
		* new email.
		* @param {Object} message Message object
		*/
		async sendToAll(message) {
			if (message.id === -1) {
				alert('Save it first')
			} else {
				this.updating = true
				try {
					const url = generateUrl(`/apps/listman/message-send/${message.id}`)
					await axios.post(url)
					const url2 = generateUrl(`/apps/listman/message-sent/${message.id}`)
					const sentDetails = await axios.post(url2)
					this.setSentDetails(sentDetails.data)
				} catch (e) {
					console.error(e)
					showError(t('listman', 'Could not signal to send the message') + e)
				}
				this.updating = false
			}
		},
		/**
		* Pick which pane to show
		* @param {String} panename Name of pane to show
		*/
		showPane(panename) {
			this.shownPane = panename
			if (panename === 'form') {
				this.subscribeFormText = this.generateSubscribeFormText()
			}
		},
		/**
		* Generate the actual text of a subscribe form
		 * @returns {string}
		*/
		generateSubscribeFormText() {
			const url = window.location.protocol + '//' + window.location.host + generateUrl('/apps/listman/subscribe/' + this.currentListRandId)
			let formText = ''
			formText += '<form method="post" action="' + url + '">' + '\n'
			formText += '\tName:<input placeholder="name" name="name"><br/>' + '\n'
			formText += '\tEmail:<input placeholder="email" name="email"><br/>' + '\n'
			formText += '\t<input type="hidden" name="redir" value="{{Your Return URL}}">' + '\n'
			formText += '\t<button>Subscribe</button>' + '\n'
			formText += '</form>' + '\n'
			return formText
		},
		/**
		* Update the subscribe form text only if it's visible
		*/
		updateSubscribeFormText() {
		  if (this.subscribeFormText != null) {
				this.subscribeFormText = this.generateSubscribeFormText()
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
					redir: '',
				})
				this.$nextTick(() => {
					this.$refs.title.focus()
				})
			}
			this.updateSubscribeFormText()
		},
		/**
		 * Abort creating a new list
		 */
		cancelNewList() {
			this.lists.splice(this.lists.findIndex((list) => list.id === -1), 1)
			this.currentListId = null
			this.updateSubscribeFormText()
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
		 * Create a new message by sending the information to the server
		 * @param {Object} message Message object
		 */
		async createMessage(message) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/listman/messages'), message)
				const index = this.currentListMessages.findIndex((match) => match.id === this.currentMessageId)
				this.$set(this.currentListMessages, index, response.data)
				this.currentMessageId = response.data.id
				message.id = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not create the message'))
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
		 * Update an existing message on the server
		 * @param {Object} message Message object
		 */
		async updateMessage(message) {
			this.updating = true
			try {
				await axios.put(generateUrl(`/apps/listman/messages/${message.id}`), message)
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not update the message'))
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
		 * Delete a message, remove it from the frontend and show a hint
		 * @param {Object} message Message object
		 */
		async deleteMessage(message) {
			try {
				const url = generateUrl(`/apps/listman/messages/${message.id}`)
				console.error('opening url to delete message:' + url)
				await axios.delete(url)
				this.currentListMessages.splice(this.currentListMessages.indexOf(message), 1)
				showSuccess(t('listman', 'Message deleted'))
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not delete the message'))
			}
		},
		/**
		 * Abort creating a new member
		 */
		cancelNewMember() {
			this.currentListMembers.splice(this.currentListMembers.findIndex((member) => member.id === -1), 1)
		},
		/**
		 * Abort creating a new message
		 */
		cancelNewMessage() {
			this.currentListMessages.splice(this.currentListMessages.findIndex((message) => message.id === -1), 1)
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
					list_id: this.currentListId,
					state: 1,
					name: '',
					email: '',
				})
				this.$nextTick(() => {
					this.$refs.name.focus()
				})
			}
		},
		/**
		 * Create a new message
		 * The message is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		newMessage() {
			if ((this.currentMessageId !== -1) && (this.currentListMessages != null)) {
			  this.currentListMessages.push({
					id: -1,
					subject: '',
					body: '',
					list_id: this.currentListId,
				})
				this.$nextTick(() => {
					this.$refs.subject.focus()
				})
			  this.currentMessageId = -1
			}
		},
		/**
		 * Open a member doesn't really do anything right now
		 * @param {Object} member Member object
		 */
		async openMember(member) {
			if (this.updating) {
				return
			}
			console.error('Opening Member', member)
		},
		/**
		* Set the sent details, the counts of messages-queued etc.
		 * @param {Object} det Details
		*/
		async setSentDetails(det) {
			console.warn('setting sent details', det)
		  this.currentMessageSentDetails.sent = det.sent
		  this.currentMessageSentDetails.queued = det.queued
		  this.currentMessageSentDetails.total = this.currentListMembers.length
		},
		/**
		 * Select a particular message and make the compose form show
		 * the contents of that message, also fetch the sent-indicators
		 * @param {Object} message Message object
		 */
		async selectMessage(message) {
			if (this.updating) {
				return
			}
			this.currentMessageId = message.id
			this.setSentDetails({ sent: '*', queued: '*' })
			const url = generateUrl(`/apps/listman/message-sent/${message.id}`)
			const sentDetails = await axios.post(url)
			this.setSentDetails(sentDetails.data)
		},
	},
}
</script>
