import { defineStore } from 'pinia'
export const useConfirmModalStore = defineStore('ConfirmModalStore', {
    state: () => {
        return {
            show: false,
            title: '',
            buttonOneText: '',
            buttonTwoText: '',
            continue: null,
            slider: false,
            sliderMin: 0,
            sliderMax: 100,
            amount: null
        }
    },
    actions: {
        clickButtonOne() {
            this.reset();


        },
        clickButtonTwo() {
            this.continue(this.amount);
            this.reset();
        },

        reset() {
            this.show = false
            this.title = '';
            this.buttonOneText = '';
            this.buttonTwoText = '';
            this.continue = null;
            this.slider = false;
            this.sliderMin = 0;
            this.sliderMax = 100;
            this.amount = null;
        }

    }
})
