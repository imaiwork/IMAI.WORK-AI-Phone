<template>
    <div v-if="modelValue">
        <div v-if="type == MaterialTypeEnum.IMAGE">
            <ElImageViewer
                v-if="previewLists.length"
                :url-list="previewLists"
                hide-on-click-modal
                :teleported="true"
                @close="handleClose" />
        </div>
        <div v-if="type == MaterialTypeEnum.VIDEO">
            <preview-video ref="playerRef" v-if="showVideo" @close="handleClose" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import { useCate, useFile } from "../../_hooks/useMaterial";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        url: string;
        type: (typeof MaterialTypeEnum)[keyof typeof MaterialTypeEnum];
    }>(),
    {
        modelValue: false,
        url: "",
        type: MaterialTypeEnum.IMAGE,
    }
);

const { currentCate } = useFile();

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
}>();

const playerRef = shallowRef();

const visible = computed({
    get() {
        return props.modelValue;
    },

    set(value) {
        emit("update:modelValue", value);
    },
});

const handleClose = () => {
    showVideo.value = false;
    emit("update:modelValue", false);
};

const showVideo = ref(false);
const previewLists = ref<any[]>([]);

watch(
    () => props.modelValue,
    (value) => {
        if (value) {
            if (props.type === MaterialTypeEnum.VIDEO) {
                showVideo.value = true;
                nextTick(() => {
                    playerRef.value?.setUrl(props.url);
                    playerRef.value?.open();
                });
            } else {
                previewLists.value = [props.url];
            }
        } else {
            nextTick(() => {
                previewLists.value = [];
                playerRef.value?.pause();
            });
        }
    }
);
</script>
