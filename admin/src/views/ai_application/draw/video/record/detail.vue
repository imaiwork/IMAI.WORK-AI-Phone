<template>
    <popup ref="popupRef" title="生成详情" width="700px" confirm-button-text="" cancel-button-text="" @close="close">
        <div class="flex flex-col gap-2">
            <div>
                <div class="text-lg font-bold mb-2">创作时间</div>
                {{ detail.create_time }}
            </div>
            <div>
                <div class="text-lg font-bold mb-2">创作参数</div>
                <div class="flex flex-wrap gap-2">
                    <el-tag>
                        {{ detail.type_name }}
                    </el-tag>
                </div>
            </div>
            <div v-if="detail.image_url">
                <div class="text-lg font-bold mb-2">参考图</div>
                <div class="flex flex-wrap gap-2">
                    <el-image
                        :src="detail.image_url"
                        :preview-src-list="[detail.image_url]"
                        fit="contain"
                        class="w-[100px]" />
                </div>
            </div>
            <div v-if="detail.desc">
                <div class="text-lg font-bold mb-2">创作提示词</div>
                {{ detail.desc }}
            </div>
            <div class="h-[500px]">
                <div class="text-lg font-bold mb-2">生成结果</div>
                <video :src="detail.video_url" controls class="w-full h-full object-cover" />
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import useAppStore from "@/stores/modules/app";

const emit = defineEmits<{
    (event: "close"): void;
}>();

const appStore = useAppStore();

const modelList = computed(() => appStore.config.draw?.channel || []);

const detail = ref<any>();

const popupRef = ref();

const getModelName = (modelId: string) => {
    return modelList.value.find((item: any) => item.id == modelId)?.name;
};

const open = (row: any) => {
    detail.value = row;
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
