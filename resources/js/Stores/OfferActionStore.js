import { defineStore } from 'pinia'
import axios from 'axios'
import toastStore from "@/Stores/ToastStore.js"; // Import your toast store

export const useOfferActionStore = defineStore('OfferActionStore', {
    state: () => {
        return {
            // Define any state if needed
        }
    },
    actions: {
        async autoRun(id) {
            console.log('auto run');
            try {
                const response = await axios.post(route('auto-accept'), {
                    offer_id: id
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
        async acceptOffer(id) {
            console.log('accepting offer');
            try {
                const response = await axios.post(route('accept-offer'), {
                    offer_id: id
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
        },
        async payEscrow(id) {
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
        },
        async payBond(id) {
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
        },
        async generateInvoice(id) {
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
        },
        async confirmPayment(id) {
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
        },
        async sendPaymentHandle(id) {
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
        },
        async collaborativeCancel(id) {
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
        },
    }
});
