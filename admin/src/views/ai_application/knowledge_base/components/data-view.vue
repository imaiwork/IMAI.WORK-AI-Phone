<template>
    <Popup ref="popupRef" title="查看详情" width="800px" @close="showModel = false">
        <el-form disabled label-position="top">
            <el-form-item prop="question" label="文档内容">
                <el-input v-model="formData.question" type="textarea" resize="none" :rows="10" clearable />
            </el-form-item>
            <el-form-item prop="answer" label="补充内容">
                <el-input v-model="formData.answer" type="textarea" resize="none" :rows="10" clearable />
            </el-form-item>
            <el-form-item label="图片">
                <div class="flex-1" v-if="images.length">
                    <ElImage
                        v-for="(item, index) in images"
                        class="w-[100px] h-[100px] bg-page rounded-md mr-[10px] mb-[10px]"
                        :hide-on-click-modal="true"
                        :preview-src-list="images"
                        :initial-index="index"
                        :preview-teleported="true"
                        fit="contain"
                        :src="item"
                        :key="index" />
                </div>
                <div v-else>无</div>
            </el-form-item>
            <el-form-item label="视频">
                <div class="flex-1" v-if="formData.video.length">
                    <MaterialFile
                        class="mr-[10px] mb-[10px]"
                        v-for="(item, index) in formData.video"
                        :key="index"
                        file-size="100px"
                        :uri="item.url"
                        type="video"
                        @click="previewVideo(item.url)" />
                </div>
                <div v-else>无</div>
            </el-form-item>
            <el-form-item label="附件">
                <div class="flex-1" v-if="formData.files?.length">
                    <div v-for="(item, index) in formData.files" :key="index">
                        <a :href="item.url" target="_blank" :download="item.name" class="text-primary">
                            {{ item.name }}
                        </a>
                    </div>
                </div>
                <div v-else>无</div>
            </el-form-item>
        </el-form>
    </Popup>
    <MaterialPreview v-model="previewState.show" :url="previewState.url" type="video" />
</template>
<script lang="ts" setup>
import { useVModels } from "@vueuse/core";

const props = withDefaults(
    defineProps<{
        modelValue: any;
        show: boolean;
    }>(),
    {}
);
const emit = defineEmits<{
    (event: "update:modelValue", value: any): void;
    (event: "update:show", value: boolean): void;
}>();

const popupRef = shallowRef();
const { modelValue: formData, show: showModel } = useVModels(props, emit);
const previewState = reactive({
    show: false,
    url: "",
});
const previewVideo = (url: string) => {
    previewState.show = true;
    previewState.url = url;
};
const images = computed(() => {
    return formData.value.images?.map(({ url }: any) => url);
});
watch(
    () => props.show,
    (value) => {
        if (value) {
            popupRef.value?.open();
        } else {
            popupRef.value?.close();
        }
    }
);
</script>
