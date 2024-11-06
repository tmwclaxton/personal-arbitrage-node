import { defineStore } from 'pinia'
export const useInternalMessagingStore = defineStore('ConfirmModalStore', {
    state: () => {
        return {
            chatRooms: [],
            selectedChatRoom: null,
            selectedChatRoomMessages: [],
            expandedInternalMessaging: false,
        }
    },
    actions: {
        open() {
            this.expandedInternalMessaging = true;
        },
        close() {
            this.expandedInternalMessaging = false;
        },

        setSelectedChatRoom(chatRoom) {
            this.selectedChatRoom = chatRoom;
        },

        retrieveChatRooms() {
            // axios.get(route('internal-messaging.chat-rooms'))
        },

        retrieveChatRoomMessages(chatRoom) {
            // axios.get(route('internal-messaging.chat-room-messages', chatRoom.id))
        },



    }
})
