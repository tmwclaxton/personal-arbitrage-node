import { defineStore } from 'pinia'
import axios from 'axios'
import toastStore from "@/Stores/ToastStore.js";
import {useConfirmModalStore} from "@/Stores/ConfirmModelStore.js"; // Import your toast store

export const useOfferActionStore = defineStore('OfferActionStore', {
    state: () => {
        return {
            // Define any state if needed
        }
    },
    actions: {
        async autoRun(offer) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Auto Run';
            useConfirmModalStore().title = 'Confirm Auto Run';
            // remove , from string
            const tempMin = offer.min_amount.replace(/,/g, '');
            const tempMax = offer.max_amount.replace(/,/g, '');
            const min = parseInt(tempMin);
            const max = parseInt(tempMax);
            if (min === 0 || max === 0) {
                useConfirmModalStore().slider = false;
            } else {
                useConfirmModalStore().slider = true;
                useConfirmModalStore().sliderMin = min;
                useConfirmModalStore().sliderMax = max;
                // convert string to number
                useConfirmModalStore().amount = Math.round(min + (max - min) / 2);

            }
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async (amount) => {
                console.log('auto run');
                try {
                    const response = await axios.post(route('auto-accept'), {
                        offer_id: offer.id,
                        amount: amount
                    });
                    toastStore.add({
                        message: 'Auto Run Started',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Auto Run Failed to Start',
                        type: "error",
                    });
                    console.log(error);
                }
            };

        },
        async uniqueRobot(id) {
            console.log('creating unique robot');
            try {
                const response = await axios.post(route('create-robot'), {
                    offer_id: id
                });
                toastStore.add({
                    message: 'Unique Robot Created',
                    type: "success",
                });
                console.log(response);
            } catch (error) {
                toastStore.add({
                    message: 'Failed to Create Unique Robot',
                    type: "error",
                });
                console.log(error);
            }
        },
        async acceptOffer(offer) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Accept Offer';
            useConfirmModalStore().title = 'Are you sure you want to accept the offer?';
            // remove , from string
            const tempMin = offer.min_amount.replace(/,/g, '');
            const tempMax = offer.max_amount.replace(/,/g, '');
            const min = parseInt(tempMin);
            const max = parseInt(tempMax);
            if (min === 0 || max === 0) {
                useConfirmModalStore().slider = false;
            } else {
                useConfirmModalStore().slider = true;
                useConfirmModalStore().sliderMin = min;
                useConfirmModalStore().sliderMax = max;
                useConfirmModalStore().amount = Math.round(min + (max - min) / 2);
            }
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async (amount) => {
                console.log('accepting offer');
                try {
                    const response = await axios.post(route('accept-offer'), {
                        offer_id: offer.id,
                        amount: amount,
                    });
                    toastStore.add({
                        message: 'Offer Accepted',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Accept Offer',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async payEscrow(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Pay Escrow';
            useConfirmModalStore().title = 'Are you sure you want to pay the escrow?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('paying escrow');
                try {
                    const response = await axios.post(route('pay-escrow'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Escrow Payment Initiated',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Pay Escrow',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async payBond(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Pay Bond';
            useConfirmModalStore().title = 'Are you sure you want to pay the bond?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('paying bond');
                try {
                    const response = await axios.post(route('pay-bond'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Bond Payment Completed',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Pay Bond',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async generateInvoice(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Generate Invoice';
            useConfirmModalStore().title = 'Are you sure you want to generate the invoice?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('generating invoice');
                try {
                    const response = await axios.post(route('update-invoice'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Invoice Generated',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Generate Invoice',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async confirmPayment(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Confirm Payment';
            useConfirmModalStore().title = 'Are you sure you want to confirm the payment?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('confirming payment');
                try {
                    const response = await axios.post(route('confirm-payment'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Payment Confirmed',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Confirm Payment',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async sendPaymentHandle(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Send Payment Handle';
            useConfirmModalStore().title = 'Are you sure you want to send the payment handle?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('sending payment handle');
                try {
                    const response = await axios.post(route('send-payment-handle'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Payment Handle Sent',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Send Payment Handle',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async sendChatMessage(id, message) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Send Chat Message';
            useConfirmModalStore().title = 'Are you sure you want to send the chat message?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('sending chat message');
                try {
                    const response = await axios.post(route('send-message'), {
                        offer_id: id,
                        message: message
                    });
                    toastStore.add({
                        message: 'Chat Message Sent',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Failed to Send Chat Message',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
        async collaborativeCancel(id) {
            useConfirmModalStore().buttonOneText = 'Cancel';
            useConfirmModalStore().buttonTwoText = 'Collaborative Cancel';
            useConfirmModalStore().title = 'Are you sure you want to cancel the collaborative?';
            useConfirmModalStore().show = true;
            useConfirmModalStore().continue = async () => {
                console.log('collaborative cancel');
                try {
                    const response = await axios.post(route('collaborative-cancel'), {
                        offer_id: id
                    });
                    toastStore.add({
                        message: 'Collaborative Cancel Successful',
                        type: "success",
                    });
                    console.log(response);
                } catch (error) {
                    toastStore.add({
                        message: 'Collaborative Cancel Failed',
                        type: "error",
                    });
                    console.log(error);
                }
            }
        },
    }
});
