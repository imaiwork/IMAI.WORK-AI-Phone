<template>
    <popup ref="popupRef" title="向量检索配置" width="680px" @confirm="save" @close="close">
        <el-form :model="formData" label-width="110px">
            <el-form-item label="检索模式">
                <el-select v-model="formData.search_mode" class="w-full" placeholder="请选择检索模式">
                    <el-option v-for="item in searchOptions" :key="item.value" :label="item.label" :value="item.value" />
                </el-select>
            </el-form-item>
            <el-form-item label="引用上限">
                <div class="flex items-center gap-4 w-full">
                    <el-slider v-model="formData.search_tokens" :min="100" :max="30000" class="flex-1" />
                    <el-input-number
                        v-model="formData.search_tokens"
                        :min="100"
                        :max="30000"
                        :controls="false"
                        class="!w-[100px]" />
                </div>
            </el-form-item>
            <el-form-item label="最低相似度" v-if="formData.search_mode === 'similar'">
                <div class="flex items-center gap-4 w-full">
                    <el-slider
                        v-model="formData.search_similar"
                        :min="0"
                        :max="1"
                        :step="0.001"
                        class="flex-1" />
                    <el-input-number
                        v-model="formData.search_similar"
                        :min="0"
                        :max="1"
                        :step="0.001"
                        :controls="false"
                        class="!w-[100px]" />
                </div>
            </el-form-item>
            <el-form-item label="结果重排">
                <el-switch v-model="formData.ranking_status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="重排权重" v-if="formData.ranking_status === 1">
                <div class="flex items-center gap-4 w-full">
                    <el-slider v-model="formData.ranking_score" :min="0" :max="1" :step="0.001" class="flex-1" />
                    <el-input-number
                        v-model="formData.ranking_score"
                        :min="0"
                        :max="1"
                        :step="0.001"
                        :controls="false"
                        class="!w-[100px]" />
                </div>
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import { cloneDeep } from "lodash";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", value: Record<string, any>): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const searchOptions = [
    { label: "混合检索", value: "mix" },
    { label: "语义检索", value: "similar" },
    { label: "全文检索", value: "full" },
];

const defaultFormData = () => ({
    search_mode: "similar",
    search_similar: 0,
    search_tokens: 8000,
    optimize_ask: 0,
    optimize_m_id: "",
    optimize_s_id: "",
    ranking_status: 0,
    ranking_score: 0,
    ranking_model: "",
});

const formData = reactive(defaultFormData());

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const save = () => {
    const params: Record<string, any> = cloneDeep(formData);
    if (params.search_mode !== "similar") delete params.search_similar;
    emit("confirm", params);
    popupRef.value?.close();
};

defineExpose({
    open,
    setFormData: (data: any) => {
        Object.assign(formData, defaultFormData());
        setFormData(data || {}, formData);
    },
});
</script>
