<template>
    <div class="flex flex-nowrap gap-2 overflow-x-auto h-full items-center">
        <div
            class="group relative inline-block text-sm text-token-text-primary file-item-group"
            v-for="(item, index) in fileList"
            :key="index">
            <files-card
                show-del-icon
                :uid="item.uid"
                :name="item.name"
                :percent="item.progress"
                :status="item.status"
                :file-size="item.size"
                :url="item.url"
                @delete="del"></files-card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { cancelRequest } from "@/utils/http";
import FilesCard from "./file-card/index.vue";
import { UPLOAD_STATUS } from "~/composables/usePasteImage";
const props = withDefaults(
    defineProps<{
        fileList: any[];
    }>(),
    {
        fileList: () => [],
    }
);
const emit = defineEmits<{
    (event: "update:file-list", value: any[]): void;
}>();

const fileList = computed({
    get() {
        return props.fileList;
    },
    set(value) {
        emit("update:file-list", value);
    },
});

const del = ({ uid, status }) => {
    const index = fileList.value.findIndex((item) => item.uid === uid);
    if (status == UPLOAD_STATUS.UPLOADING) {
        const { requestKey } = fileList.value[index];
        cancelRequest(requestKey);
    }
    fileList.value.splice(index, 1);
};
</script>

<style scoped></style>
