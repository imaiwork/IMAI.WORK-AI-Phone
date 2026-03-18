<template>
    <div class="h-full flex flex-col bg-slate-50">
        <UploadTemplate.define v-slot="{ type, label, subLabel }">
            <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9] transition-all">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div>
                        <span class="text-[14px] font-[900] text-[#1E293B]">{{ label }}</span>
                        <span class="ml-2 text-[10px] text-[#94A3B8] font-medium uppercase tracking-wider">{{
                            subLabel
                        }}</span>
                    </div>
                    <div
                        v-if="formData[type]"
                        class="text-[11px] text-primary font-black cursor-pointer hover:underline"
                        @click.stop="formData[type] = ''">
                        清除重置
                    </div>
                </div>

                <upload
                    class="w-full"
                    show-progress
                    drag
                    :show-file-list="false"
                    :accept="accept"
                    :ratio-size="[ratioSize[0], ratioSize[1]]"
                    :image-resolution="[imageResolution[0], imageResolution[1]]"
                    :max-size="maxSize"
                    @success="getUploadImage($event, type)">
                    <div
                        class="h-[180px] w-full flex flex-col items-center justify-center relative rounded-[18px] border-2 border-dashed border-br bg-[#FBFDFF] hover:border-[#0065fb] hover:bg-[#F5F7FF] transition-all overflow-hidden group">
                        <template v-if="formData[type]">
                            <img
                                :src="formData[type]"
                                class="absolute inset-0 w-full h-full object-cover blur-xl opacity-20 scale-110" />
                            <img :src="formData[type]" class="relative z-10 w-full h-full object-contain p-2" />
                            <div
                                class="absolute inset-0 bg-[#000000]/20 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center justify-center">
                                <div class="px-4 py-1.5 bg-white rounded-full text-xs font-black text-[#1E293B]">
                                    更换素材
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="w-12 h-12 rounded-2xl bg-[#0065fb]/10 flex items-center justify-center text-primary mb-3 group-hover:scale-110 transition-transform">
                                <Icon name="el-icon-UploadFilled" :size="28"></Icon>
                            </div>
                            <div class="text-[13px] font-black text-[#64748B]">点击或拖拽上传图片</div>
                            <div class="text-[10px] text-[#94A3B8] mt-1 font-medium">支持 JPG/PNG/WEBP</div>
                        </template>
                    </div>
                </upload>
            </div>
        </UploadTemplate.define>
        <div class="shrink-0 px-6 h-[72px] flex items-center bg-white border-b border-[#F1F5F9]">
            <div class="flex p-1 rounded-[16px] w-full bg-[#F1F5F9]">
                <div
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    @click="formData.type = tab.value"
                    class="flex-1 py-2 text-center cursor-pointer transition-all duration-300 rounded-[12px] text-[13px] font-[900]"
                    :class="
                        formData.type === tab.value ? 'bg-white text-primary ' : 'text-[#64748B] hover:text-[#1E293B]'
                    ">
                    {{ tab.label }}
                </div>
            </div>
        </div>
        <ElScrollbar>
            <div class="p-4 space-y-3">
                <div class="space-y-4">
                    <template v-if="formData.type == FashionImageTypeEnum.UPPER_LOWER_CLOTHES">
                        <UploadTemplate.reuse type="upper_clothes" label="上传上装" subLabel="Top Wear" />
                        <UploadTemplate.reuse type="lower_clothes" label="上传下装" subLabel="Bottom Wear" />
                    </template>
                    <template v-if="formData.type == FashionImageTypeEnum.DRESS">
                        <UploadTemplate.reuse type="dress" label="上传全身装" subLabel="Full Dress" />
                    </template>
                </div>

                <div class="bg-white rounded-[24px] p-5 border border-[#F1F5F9]">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <div>
                            <span class="text-[14px] font-[900] text-[#1E293B]">选择模特</span>
                            <span class="ml-2 text-[10px] text-[#94A3B8] font-medium uppercase tracking-wider"
                                >Select Model</span
                            >
                        </div>
                        <div class="text-[11px] text-[#94A3B8] font-medium">{{ formData.persons.length }} / 4 已选</div>
                    </div>

                    <div
                        class="text-[11px] text-end font-black text-[#64748B] cursor-pointer hover:text-primary transition-all mb-2"
                        @click="openModelImage">
                        更多模特
                    </div>

                    <div class="w-full h-[450px]">
                        <ElScrollbar>
                            <div class="grid grid-cols-3 gap-2 p-1">
                                <upload
                                    drag
                                    show-progress
                                    :show-file-list="false"
                                    :accept="accept"
                                    :max-size="5"
                                    :image-resolution="[4096, 4096]"
                                    @success="getUploadModelImage">
                                    <div
                                        class="h-[140px] w-full flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-br bg-gray-50/50 hover:border-primary hover:bg-blue-50/50 transition-all cursor-pointer group">
                                        <div
                                            class="w-10 h-10 rounded-full bg-white flex items-center justify-center mb-2 group-hover:scale-110 transition-all duration-300">
                                            <Icon name="el-icon-Plus" :size="20" color="var(--color-primary)"></Icon>
                                        </div>
                                        <span class="text-xs font-black text-tx-secondary">添加模特素材</span>
                                        <span class="text-[10px] text-tx-placeholder mt-1 font-medium"
                                            >支持 JPG/PNG 格式</span
                                        >
                                    </div>
                                </upload>

                                <div
                                    v-for="(item, index) in optionsData.modelList"
                                    :key="index"
                                    class="relative h-[140px] w-full rounded-xl overflow-hidden cursor-pointer group transition-all duration-300 border-2"
                                    :class="
                                        formData.persons.includes(item.result_image)
                                            ? 'border-primary scale-[0.98]'
                                            : 'border-[transparent]'
                                    "
                                    @click="handleModelImageClick(item.result_image)">
                                    <ElImage :src="item.result_image" class="w-full h-full" fit="cover" />

                                    <div
                                        v-if="formData.persons.includes(item.result_image)"
                                        class="absolute top-2 right-2 w-5 h-5 bg-primary rounded-full flex items-center justify-center shadow-sm z-10">
                                        <Icon name="el-icon-Check" :size="12" color="#ffffff" />
                                    </div>

                                    <div
                                        class="absolute inset-0 opacity-0 rounded-xl group-hover:opacity-100 bg-black/20 backdrop-blur-[2px] transition-all duration-300 flex flex-col items-center justify-center gap-3">
                                        <div class="flex gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/40 flex items-center justify-center backdrop-blur-md transition-colors"
                                                @click.stop="previewModelImage(index)">
                                                <Icon name="el-icon-FullScreen" :size="18" color="#ffffff" />
                                            </div>
                                            <div
                                                class="w-8 h-8 rounded-lg bg-red-500/80 hover:bg-red-600 flex items-center justify-center backdrop-blur-md transition-colors"
                                                @click.stop="handleDeleteModelImage(index)">
                                                <Icon name="el-icon-Delete" :size="18" color="#ffffff" />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="absolute bottom-0 left-0 w-full h-1/3 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
                                </div>
                            </div>
                        </ElScrollbar>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
    <case-btn @on-click="opeCaseImage" />
    <ElImageViewer
        v-if="showPreview"
        :initial-index="previewIndex"
        :url-list="previewUrl"
        @close="showPreview = false"></ElImageViewer>
    <case-image-v2 ref="caseImageRef" type="fashion" @choose="handleChooseCase" />
    <case-image-v2 ref="modelImageRef" type="model" @choose="handleChooseModel" />
</template>

<script setup lang="ts">
import { getCaseLists, addModelCase, deleteModelCase } from "@/api/drawing";
import { createReusableTemplate } from "@vueuse/core";
import { FashionImageTypeEnum } from "../_enums";
import CaseImageV2 from "./case-image-v2.vue";
import CaseBtn from "./case-btn/index.vue";

const emit = defineEmits<{
    (event: "update:formData", value: any): void;
    (event: "generatePrompt", value: { promptId: number; prompt: string }): void;
}>();

const typeTabs = [
    { label: "上下装", value: FashionImageTypeEnum.UPPER_LOWER_CLOTHES },
    { label: "连衣裙", value: FashionImageTypeEnum.DRESS },
];

const formData = reactive({
    type: FashionImageTypeEnum.UPPER_LOWER_CLOTHES,
    dress: "",
    upper_clothes: "",
    lower_clothes: "",
    persons: [],
    img_count: 0,
});

// 图片上传 Start

const accept = ".jpg,.jpeg,.png";
const maxSize = 10;
const ratioSize = [1 / 3, 3];
const imageResolution = [4096, 4096];

const getUploadImage = (result: any, type: string) => {
    const uri = result.data.uri;
    formData[type] = uri;
    // if (formData.type == FashionImageTypeEnum.DRESS) {
    //     formData.upper_clothes = uri;
    //     formData.lower_clothes = formData.upper_clothes;
    //     return;
    // }
};

// 图片上传 End

// 模特类型 Start

const { optionsData } = useDictOptions<{
    modelList: any[];
}>({
    modelList: {
        api: getCaseLists,
        params: {
            case_type: 4,
            page_size: 999,
        },
        transformData: (data) => data.lists.map((item: any) => ({ ...item, result_image: item.result_image })),
    },
});

const getUploadModelImage = (result: any) => {
    const uri = result.data.uri;
    optionsData.modelList.unshift({ result_image: uri });
    addModelCase({ result_image: uri });
};

const handleModelImageClick = (img: string) => {
    // 判断是否存在
    if (formData.persons.includes(img)) {
        formData.persons = [];
    } else {
        formData.persons = [img];
    }
    formData.img_count = formData.persons.length;
};

const handleDeleteModelImage = (index: number) => {
    useNuxtApp().$confirm({
        title: "提示",
        message: "确定删除该模特吗？",
        onConfirm: async () => {
            const data = optionsData.modelList[index];
            if (data.user_id) {
                try {
                    await deleteModelCase({ id: data.id });
                    feedback.msgSuccess("删除成功");
                    optionsData.modelList.splice(index, 1);
                    formData.persons.splice(index, 1);
                } catch (error) {
                    feedback.msgError("删除失败");
                }
            }
        },
    });
};

const showPreview = ref(false);
const previewIndex = ref(0);
const previewUrl = ref([]);
const previewModelImage = (index: number) => {
    showPreview.value = true;
    previewIndex.value = index;
    previewUrl.value = optionsData.modelList.map((item) => item.result_image);
};

const modelImageRef = ref();
const openModelImage = () => {
    modelImageRef.value.open();
};

const handleChooseModel = (res: any) => {
    const { data } = res;
    const img = data.result_image;
    // 判断是否存在
    const item = optionsData.modelList.find((item) => item.result_image == img);
    if (item) {
        formData.persons = [img];
        if (!formData.persons.includes(img)) {
            formData.persons.push(img);
        }
        return;
    }
    optionsData.modelList.unshift(item);
    formData.persons = [img];
};

// 模特类型 End

// 优秀案例 Start

const caseImageRef = ref();
const opeCaseImage = () => {
    caseImageRef.value.open();
};

const handleChooseCase = (res: any) => {
    const { case_type, data } = res;
    if (case_type == 0) {
        formData.upper_clothes = data.params?.images[0];
        formData.lower_clothes = data.params?.images[1];
        formData.type = FashionImageTypeEnum.UPPER_LOWER_CLOTHES;
    }
    if (case_type == 1) {
        formData.dress = data.result_image;
        formData.upper_clothes = formData.dress;
        formData.lower_clothes = formData.dress;
        formData.type = FashionImageTypeEnum.DRESS;
    }
};

// 优秀案例 End

// 模板渲染 Start

const UploadTemplate = createReusableTemplate<{
    type: string;
    label: string;
    subLabel: string;
}>();

// 模板渲染 End

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
        if (formData.type == FashionImageTypeEnum.DRESS) {
            formData.upper_clothes = formData.dress;
            formData.lower_clothes = formData.dress;
        }
        return {
            params: {
                upper_clothes: formData.upper_clothes,
                lower_clothes: formData.lower_clothes,
                persons: formData.persons,
                img_count: formData.persons.length,
            },
            type: formData.type,
            type_name: typeTabs.find((item) => item.value == formData.type)?.label,
        };
    },
    validateForm: () => {
        return new Promise((resolve, reject) => {
            const isDress = formData.type === FashionImageTypeEnum.DRESS;

            if (isDress && !formData.dress) {
                feedback.msgWarning("请上传全身装");
                return reject(false);
            }

            if (!isDress && !formData.upper_clothes) {
                feedback.msgWarning("请上传上装");
                return reject(false);
            }

            if (!isDress && !formData.lower_clothes) {
                feedback.msgWarning("请上传下装");
                return reject(false);
            }

            if (formData.persons.length === 0) {
                feedback.msgWarning("请选择模特");
                return reject(false);
            }

            resolve(true);
        });
    },
});
</script>

<style scoped lang="scss">
:deep(.el-upload-dragger) {
    border: none !important;
}
</style>
