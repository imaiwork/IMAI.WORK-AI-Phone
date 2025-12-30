import { defineStore } from "pinia";

interface MaterialState {
    anchorList: any[];
    videoList: any[];
    imageList: any[];
    marketingList: any[];
    clueList: any[];
}

export type ListName = keyof MaterialState;

const useMaterialStore = defineStore("material", {
    state: (): MaterialState => ({
        anchorList: [],
        videoList: [],
        imageList: [],
        marketingList: [],
        clueList: [],
    }),
    actions: {
        setList(listName: ListName, list: any[]) {
            this[listName] = list;
        },
        addToList(listName: ListName, items: any[]) {
            this[listName].push(...items);
        },
        removeFromList(listName: ListName, index: number) {
            this[listName].splice(index, 1);
        },
        clearMaterial() {
            this.$reset();
        },
    },
});

export default useMaterialStore;
