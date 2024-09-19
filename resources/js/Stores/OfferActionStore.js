import { defineStore } from 'pinia'
import axios from 'axios'

export const useOfferActionStore = defineStore('OfferActionStore', {
    state: () => {
        return {

        }
    },
    actions: {

        async autoRun(id) {
            console.log('auto run');
            try {
                const response = await axios.post(route('auto-accept'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async uniqueRobot(id) {
            console.log('creating unique robot');
            try {
                const response = await axios.post(route('create-robot'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async acceptOffer(id) {
            console.log('accepting offer');
            try {
                const response = await axios.post(route('accept-offer'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async payEscrow(id) {
            console.log('paying escrow');
            try {
                const response = await axios.post(route('pay-escrow'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async payBond(id) {
            console.log('paying bond');
            try {
                const response = await axios.post(route('pay-bond'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async generateInvoice(id) {
            console.log('generating invoice');
            try {
                const response = await axios.post(route('update-invoice'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async confirmPayment(id) {
            console.log('confirming payment');
            try {
                const response = await axios.post(route('confirm-payment'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async sendPaymentHandle(id) {
            console.log('sending payment handle');
            try {
                const response = await axios.post(route('send-payment-handle'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async collaborativeCancel(id) {
            console.log('collaborative cancel');
            try {
                const response = await axios.post(route('collaborative-cancel'), {
                    offer_id: id
                });
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    }
})
