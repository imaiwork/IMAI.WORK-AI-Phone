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
                    <el-tag> 生成数量：{{ detail.params?.img_count || 1 }} </el-tag>
                    <el-tag>
                        {{ getModelName(detail.model_type) }}
                    </el-tag>
                    <el-tag v-if="detail.type_name">
                        {{ detail.type_name }}
                    </el-tag>
                    <el-tag v-if="detail.params?.aspect_ratio">
                        {{ detail.params?.aspect_ratio }}
                    </el-tag>
                    <el-tag v-if="detail.params?.height">
                        {{ detail.params?.height }}*{{ detail.params?.width }}
                    </el-tag>
                    <el-tag v-if="detail.params?.resolution">
                        {{ detail.params?.resolution.join("*") }}
                    </el-tag>
                    <el-tag v-if="detail.params?.style"> 风格：{{ detail.params?.style }} </el-tag>
                    <el-tag v-if="detail.params?.template_category">
                        模版类型：{{ detail.params?.template_category }}
                    </el-tag>
                    <el-tag v-if="detail.params?.template_name"> 模版名称：{{ detail.params?.template_name }} </el-tag>
                    <el-tag v-if="detail.params?.template_name_zh">
                        模型中文：{{ detail.params?.template_name_zh }}
                    </el-tag>
                </div>
                <div class="mt-2">
                    <div class="flex flex-wrap gap-2">
                        <div class="border border-[#f0f0f0] rounded-md p-2" v-if="detail.params.upper_clothes">
                            <image-contain
                                :src="detail.params.upper_clothes"
                                width="50"
                                height="50"
                                fit="contain"
                                :preview-src-list="[detail.params.upper_clothes]" />
                        </div>
                        <div
                            class="border border-[#f0f0f0] rounded-md p-2"
                            v-if="
                                detail.params.lower_clothes &&
                                detail.params.upper_clothes != detail.params.lower_clothes
                            ">
                            <image-contain
                                :src="detail.params.lower_clothes"
                                width="50"
                                height="50"
                                fit="contain"
                                :preview-src-list="[detail.params.lower_clothes]" />
                        </div>
                        <div
                            class="border border-[#f0f0f0] rounded-md p-2"
                            v-if="detail.params.persons && detail.params.persons.length > 0">
                            <image-contain
                                :src="detail.params.persons[0]"
                                width="50"
                                height="50"
                                fit="contain"
                                :preview-src-list="detail.params.persons" />
                        </div>
                        <div v-if="detail.params.image" class="mt-2">
                            <div class="text-lg font-bold mb-2">参考图</div>
                            <image-contain
                                :src="detail.params.image"
                                width="50"
                                height="50"
                                fit="contain"
                                :preview-src-list="[detail.params.image]" />
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="detail.params?.prompt">
                <div class="text-lg font-bold mb-2">创作提示词</div>
                {{ detail.params?.prompt }}
            </div>

            <div class="h-[500px]">
                <div class="text-lg font-bold mb-2">生成结果</div>
                <el-scrollbar>
                    <div v-for="(item, index) in detail.images" class="rounded-md border-[1px] border-[#f0f0f0] p-2">
                        <el-image :src="item.image" :preview-src-list="[item.image]" />
                    </div>
                </el-scrollbar>
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

const modelList = computed(() => appStore.config.hd?.channel || []);

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
