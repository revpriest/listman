<template>
	<div id="content" class="app-listman">
		<NcAppNavigation>
			<NcAppNavigationNew v-if="!loading"
				:text="t('listman', 'New list!')"
				:disabled="false"
				button-id="new-listman-button"
				button-class="icon-add"
				@click="newList" />
			<ul>
				<NcAppNavigationItem v-for="list in lists"
					:key="list.id"
					:title="list.title ? list.title : t('listman', 'New list!')"
					:class="{active: currentListId === list.id}"
					@click="openList(list)">
					<template slot="actions">
						<NcActionButton v-if="list.id === -1"
							icon="icon-close"
							@click="cancelNewList(list)">
							{{ t('listman', 'Cancel list creation') }}
						</NcActionButton>
						<NcActionButton v-else
							icon="icon-delete"
							@click="deleteList(list)">
							{{ t('listman', 'Delete list') }}
						</NcActionButton>
					</template>
				</NcAppNavigationItem>
			</ul>
			<div v-if="isAdmin" class="settingsSection">
				<ul id="queueDetails">
					<li>Queued: <span id="queued">{{ currentQueue.queued }}</span> messages</li>
					<li>Rate: <span id="queued">{{ currentQueue.rate }}</span> per 5 mins</li>
				</ul>
				<h3 @click="toggleSettings()">
					Settings
				</h3>
				<ul v-if="settingsToggle" id="settingsPanel">
					<li>
						Max Send Per Day:<input ref="settings.maxdaily"
							v-model="settings.maxdaily"
							type="input"
							placeholder="SMTP Host">
					</li>
					<li>
						SMTP Host:<input ref="settings.host"
							v-model="settings.host"
							type="input"
							placeholder="SMTP Host">
					</li>
					<li>
						SMTP User:<input ref="settings.user"
							v-model="settings.user"
							type="input"
							placeholder="SMTP User">
					</li>
					<li>
						SMTP Pass:<input ref="settings.pass"
							v-model="settings.pass"
							type="password"
							placeholder="SMTP Pass">
					</li>
					<li>
						SMTP Port:<input ref="settings.port"
							v-model="settings.port"
							type="input"
							placeholder="SMTP port">
					</li>
					<li>
						SMTP Secure (ssl/tls):<input ref="settings.smtpSecure"
							v-model="settings.smtpSecure"
							type="input"
							placeholder="ssl">
					</li>
					<li>
						<input type="button"
							class="primary"
							:value="t('listman', 'Save Settings')"
							@click="updateSettings(true)">
					</li>
					<li>
						Latest Warn:<input ref="settings.host"
							v-model="settings.latestWarn"
							type="input"
							placeholder="warnings appear here">
					</li>
				</ul>
			</div>
		</NcAppNavigation>
		<NcAppContent>
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
						class="listman_listInput"
						:disabled="updating">
					<textarea ref="desc"
						v-model="currentList.desc"
						placeholder="description"
						class="listman_listInput"
						:disabled="updating" />
					<input ref="fromname"
						v-model="currentList.fromname"
						placeholder="The name to use in the 'from' section of the email headers"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="fromEmail"
						v-model="currentList.fromemail"
						placeholder="The email to use in the 'from' section of the email headers"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="buttontext"
						v-model="currentList.buttontext"
						placeholder="The text to write in the optional action button at the end of each email"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="shareurl"
						v-model="currentList.shareurl"
						placeholder="The url for the share button"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="suburl"
						v-model="currentList.suburl"
						placeholder="The url for the subscribe button"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="buttonlink"
						v-model="currentList.buttonlink"
						placeholder="The link to use in the optional action button at the end of each email"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="footer"
						v-model="currentList.footer"
						placeholder="A footer at the bottom of each email"
						type="text"
						class="listman_listInput"
						:disabled="updating">
					<input ref="redir"
						v-model="currentList.redir"
						placeholder="url to return to after un/subscribe confirmation (optional)"
						type="text"
						class="listman_listInput"
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
									<option value="-2">
										Awaiting Resend
									</option>
								</select>
								<input type="button"
									class="primary"
									:value="t('listman', 'Save')"
									:disabled="updating || !savePossible"
									@click="saveMember(member)">
								<div class="listman_memberactions">
									<NcActionButton v-if="member.id === -1"
										icon="icon-close"
										class="listman_memberaction"
										@click="cancelNewMember(member)">
										{{ t('listman', '') }}
									</NcActionButton>
									<NcActionButton v-else
										icon="icon-delete"
										class="listman_memberaction"
										@click="deleteMember(member)">
										{{ t('listman', '') }}
									</NcActionButton>
								</div>
							</div>
						</li>
					</ul>
					<div>
						<input type="button"
							class="primary"
							:value="t('listman', 'New Member')"
							:disabled="updating"
							@click="newMember">
					</div>
					<div class="groupUI">
						<input id="csvfile-member"
							type="file"
							class="primary"
							:disabled="updating">
						<input type="button"
							class="primary"
							:value="t('listman', 'Import List')"
							:disabled="updating"
							@click="importMember">
					</div>
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
								<NcActionButton v-if="message.id === -1"
									icon="icon-close"
									class="listman_messageaction"
									@click="cancelNewMessage(message)" />
								<NcActionButton v-else
									icon="icon-delete"
									class="listman_messageaction"
									@click="deleteMessage(message)" />
							</div>
							<div v-if="currentMessageId==message.id"
								id="listman_messagedetails">
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
								<input
									id="listman_view"
									type="button"
									class="primary"
									:value="t('listman', 'Web View')"
									:disabled="updating || !savePossible"
									@click="webView(message,'view')">
								<input
									id="listman_md"
									type="button"
									class="primary"
									:value="t('listman', 'Markdown View')"
									:disabled="updating || !savePossible"
									@click="webView(message,'md')">
								<input
									id="listman_stat"
									type="button"
									class="primary"
									:value="t('listman', 'Stats')"
									:disabled="updating || !savePossible"
									@click="webView(message,'stats')">
								<input
									id="listman_widget"
									type="button"
									class="primary"
									:value="t('listman', 'Widget')"
									:disabled="updating || !savePossible"
									@click="webView(message,'widget')">
								<input
									id="listman_sendtoall"
									type="button"
									class="primary"
									:value="t('listman', 'Send To All')"
									:disabled="updating || !savePossible"
									@click="sendToAll(message)">
								<span id="listman_numsent">
									<span
										id="listman_numsent_queued"
										:title="t('listman', 'queued')">
										{{ currentMessageSentDetails.queued }}
									</span> /
									<span
										id="listman_numsent_sent"
										:title="t('listman', 'sent')">
										{{ currentMessageSentDetails.sent }}
									</span> /
									<span
										id="listman_numsent_total"
										:title="t('listman', 'in-list')">
										{{ currentMessageSentDetails.total }}
									</span>
									{{ t('listman', 'sent') }}
								</span>
                <div>
                  You May Use, on a new line:
                  <pre style="border: 1px solid red;font-family: monospace;border-radius:.3em">
/h1 headline
/img URL alt text
/link URL link text
/*link URL link text
"
Blockquote
"
                  </pre>
                </div>
							</div>
						</li>
					</ul>
				</div>
				<!-- Show Subscribe Form -->
				<div v-if="shownPane=='form'" id="shownPane">
					<h2>{{ currentList.title }} - {{ t('listman', 'Subscribe-form code.') }}</h2>
					<pre id="subscribeFormText">{{ subscribeFormText }}</pre>
					<hr>
					<a :href="subscribeFormUrl" class="btn">{{ t('listman', 'Link To Native Form') }}</a>
				</div>
			</div>
			<div v-else id="emptydesc">
				<div class="icon-file" />
				<p class="listman_empty">
					{{ t('listman', 'Select or create a list from the menu on the left') }}
				</p>
			</div>
		</NcAppContent>
	</div>
</template>

<script>
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem'
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew'

import '@nextcloud/dialogs/styles/toast.scss'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'

export default {
	name: 'App',
	components: {
		NcActionButton,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
	},
	data() {
		return {
	    isAdmin: false,
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
			currentQueue: {
				queued: 0,
				rate: 0,
			},
			shownPane: 'details',
			updating: false,
			loading: true,
			subscribeFormText: null,
			subscribeFormUrl: '',
			settingsToggle: false,
			settings: {
				host: '',
				user: '',
				pass: '',
				port: '',
				maxdaily: '50',
				latestWarn: '-',
			},
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
			setInterval(this.updateQueueMonitor, 1000)
			const currentUser = getCurrentUser()
			if (currentUser.isAdmin) {
				this.isAdmin = true
				this.updateSettings(false)
			}
		} catch (e) {
			console.error(e)
			showError(t('listman', 'Could not fetch lists'))
		}
		this.loading = false
	},

	methods: {
		/**
		 * Create a new list
		 * @param {Object} list List object
		 */
		async openList(list) {
			console.error('Opening List')
			if (this.updating) {
				return
			}
			this.currentListId = list.id

			// Fill members-list
      this.refreshListDetails()
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
		* Action to signal the server to save the settings
		* @param {Boolean} savefirst Should we save existing settings, or just load?
		*/
		async updateSettings(savefirst) {
			let senddat = []
			try {
				if (savefirst) {
					senddat = this.settings
				}
				const url = generateUrl('/apps/listman/settings')
				const reply = await axios.post(url, senddat)
				this.settings = reply.data
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not save settings'))
			}
		},
		/**
		* Open a new window/tab with the web-view of the message.
		* @param {Object} message Message object
		* @param {String} method String View or stats or something
		*/
		async webView(message, method = 'view') {
			if (message.id === -1) {
				alert('Save it first')
			} else {
				try {
					const url = generateUrl('/apps/listman/message-' + method + '/' + message.randid)
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
    * Reload the list details,
    * maybe coz we just did a "create"
    */
    async refreshListDetails() {
			const url = generateUrl('/apps/listman/listdetails/' + this.currentListId)
			const response = await axios.get(url)
			this.currentListMembers = response.data.members
			this.currentListMessages = response.data.messages
			this.currentListRandId = response.data.list.randid
			this.currentMessageId = null
			this.updateSubscribeFormText()
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
			formText += '\tEmail:<input placeholder="email" name="email"><br/><br/>' + '\n'
			formText += '\tTo prove you\'re not a robot: Type hello.<br/>\n'
		  formText += '\t<input placeholder="hello" name="hello"><br/>' + '\n'
			formText += '\t<input type="hidden" name="redir" value="{{Your Return URL}}">' + '\n'
			formText += '\t<button>Subscribe</button>' + '\n'
			formText += '</form>' + '\n'
			this.subscribeFormUrl = window.location.protocol + '//' + window.location.host + generateUrl('/apps/listman/subscribe/' + this.currentListRandId)
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
		 * Create a new list 
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
					fromname: '',
					fromemail: '',
					buttontext: 'more',
					shareurl: '',
					suburl: '',
					buttonlink: '',
					footer: '',
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
				this.currentListRandId = response.data.randid

        //Update the list of lists since there's a new one.
        const listresponse = await axios.get(generateUrl('/apps/listman/lists'))
        this.lists = listresponse.data

        alert("Saved new list:"+this.currentListId);
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
        this.refreshListDetails()
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
		 * Toggle the settings dialogue visibility
		 * @param {Object} list List object
		 */
		async toggleSettings() {
			this.settingsToggle = !this.settingsToggle
		},
		/**
		 * Delete a list, remove it from the frontend and show a hint
		 * @param {Object} list List object
		 */
		async deleteList(list) {
			if (!confirm(t('listman', 'Do you really want to delete this whole list?'))) {
				return
			}
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
			if (!confirm(t('listman', 'Do you really want to delete this member?'))) {
				return
			}
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
			if (!confirm(t('listman', 'Do you really want to delete this message?'))) {
				return
			}
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
        this.refreshListDetails()
			} catch (e) {
				console.error(e)
				showError(t('listman', 'Could not create the member'))
			}
			this.updating = false
		},
		/**
		* Import new members from CSV file, we read it and add it to the screen
		* and the DB
		*/
		async importMember() {
			const file = document.querySelector('#csvfile-member').files[0]
			const reader = new FileReader()
			const fileName = file.name
			reader.readAsBinaryString(file)
			const that = this

			reader.onload = function(e) {
				const objCsv = e.target.result
				const allTextLines = objCsv.split(/\r\n|\n/)

				for (let i = 1; i < allTextLines.length - 1; i++) {
					const data = allTextLines[i].split(',')
					that.currentListMembers.push({
						id: -1,
						list_id: that.currentListId,
						state: 1,
						name: data[0],
						email: data[1],
					})
					const member = { id: -1, name: data[0], email: data[1], list_id: that.currentListId, state: 1 }
					that.createMember(member)
				}
			}
			reader.onerror = function() { alert('Unable to read ' + fileName) }
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
		* Periodically update the queue-monitor
		*/
		async updateQueueMonitor() {
			const url = generateUrl(`/apps/listman/message-sent/${this.currentMessageId}`)
			const sentDetails = await axios.post(url)
			this.setSentDetails(sentDetails.data)
			console.warn(sentDetails.data)
		},

		/**
		* Set the sent details, the counts of messages-queued etc.
		* @param {Object} det Details
		*/
		async setSentDetails(det) {
			if (det.current) {
				this.currentMessageSentDetails.sent = det.current.sent
				this.currentMessageSentDetails.queued = det.current.queued
			} else {
				this.currentMessageSentDetails.sent = 'x'
				this.currentMessageSentDetails.queued = 'x'
			}
			if (this.currentListMembers) {
				const subscribed = this.currentListMembers.filter((member) => member.state > 0)
				console.warn('Counted Subscribed...', subscribed)
				this.currentMessageSentDetails.total = subscribed.length
				console.warn('So length is ', this.currentMessageSentDetails.total)
			} else {
				this.currentMessageSentDetails.total = 0
			}

			if (det.all) {
				this.currentQueue.queued = det.all.queued
				this.currentQueue.rate = det.all.rate
			}
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
