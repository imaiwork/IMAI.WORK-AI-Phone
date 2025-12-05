<template>
    <div class="h-full flex flex-col">
        <DefineTemplate v-slot="{ type }">
            <upload
                class="w-full h-full"
                show-progress
                drag
                :show-file-list="false"
                :accept="accept"
                :ratio-size="[ratioSize[0], ratioSize[1]]"
                :max-size="maxSize"
                @success="getUploadImage($event, type)">
                <div class="h-[164px] w-full flex flex-col items-center justify-center leading-[1.5] relative">
                    <template v-if="formData[type]">
                        <img :src="formData[type]" class="w-full h-full object-contain" />
                        <div class="absolute top-1 right-1 cursor-pointer w-6 h-6" @click.stop="formData[type] = ''">
                            <close-btn />
                        </div>
                        <div
                            class="absolute bottom-3 cursor-pointer px-[10px] text-white rounded-full border border-[#ffffff1a] bg-[#ffffff4d] shadow-[0px_6px_12px_0px_rgba(0,0,0,0.24)] h-[28px] flex items-center justify-center">
                            更换图片
                        </div>
                    </template>
                    <template v-else>
                        <Icon name="local-icon-file_add" :size="28" color="#ffffff"></Icon>
                        <div class="text-xs text-white mt-3">点此上传图片 支持拖拽上传</div>
                        <div class="text-xs text-[#ffffff4d] mt-3 mx-[50px]">
                            单个文件不超过{{ maxSize }}MB，宽高比小于{{ ratioSize[0] }}/{{
                                ratioSize[1]
                            }}，请勿上传gif格式图片
                        </div>
                    </template>
                </div>
            </upload>
        </DefineTemplate>
        <div class="type-tabs">
            <ElTabs v-model="formData.type">
                <ElTabPane
                    v-for="(tab, index) in typeTabs"
                    :name="tab.value"
                    :label="tab.label"
                    :key="index"></ElTabPane>
            </ElTabs>
        </div>
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="p-4">
                    <ElForm :model="formData" label-position="top">
                        <template v-if="formData.type == FashionImageTypeEnum.UPPER_LOWER_CLOTHES">
                            <ElFormItem label="上衣部分">
                                <div class="w-full">
                                    <UploadTemplate type="upper_clothes" />
                                </div>
                            </ElFormItem>
                            <ElFormItem label="下装部分">
                                <div class="w-full">
                                    <UploadTemplate type="lower_clothes" />
                                </div>
                            </ElFormItem>
                        </template>
                        <ElFormItem label="连衣裙部分" v-if="formData.type == FashionImageTypeEnum.DRESS">
                            <div class="w-full">
                                <UploadTemplate type="upper_clothes" />
                            </div>
                        </ElFormItem>
                        <ElFormItem>
                            <template #label>
                                <div class="flex justify-between gap-2">
                                    <div>
                                        <div class="text-white">模特类型</div>
                                        <div class="text-[#ffffff4d] text-[11px]">提示：上传模特请选择模特类型图片</div>
                                    </div>
                                    <ElButton type="primary" size="small" @click="openModelImage">更多模特</ElButton>
                                </div>
                            </template>
                            <div class="w-full h-[420px]">
                                <ElScrollbar>
                                    <div class="grid grid-cols-3 gap-2">
                                        <upload
                                            drag
                                            show-progress
                                            :show-file-list="false"
                                            :accept="accept"
                                            @success="getUploadModelImage">
                                            <div
                                                class="h-[127px] w-full flex-col rounded-md flex items-center justify-center bg-app-bg-3">
                                                <Icon name="local-icon-file_add" :size="28" color="#ffffff"></Icon>
                                                <span class="mt-2 text-[11px] text-[#ffffff80]">点击添加模特</span>
                                            </div>
                                        </upload>
                                        <div
                                            v-for="(item, index) in optionsData.modelList"
                                            :key="index"
                                            class="relative h-[130px] w-full flex-col rounded-md flex items-center justify-center bg-app-bg-3 overflow-hidden border-[2px] border-[#ffffff33] cursor-pointer group"
                                            :class="
                                                formData.persons.includes(item.result_image) ? 'border-primary' : ''
                                            "
                                            @click="handleModelImageClick(item.result_image)">
                                            <ElImage :src="item.result_image" class="w-full h-full" fit="cover" />
                                            <div
                                                class="absolute top-0 left-0 w-full h-full invisible group-hover:visible bg-[var(--el-overlay-color-lighter)] flex items-center justify-center">
                                                <div
                                                    class="cursor-pointer absolute top-0 right-0"
                                                    @click.stop="previewModelImage(index)">
                                                    <Icon name="local-icon-fullscreen" :size="32"></Icon>
                                                </div>
                                                <ElButton
                                                    color="#FF3C26"
                                                    class="w-[68px] !h-7 !rounded-full"
                                                    @click="handleDeleteModelImage(index)"
                                                    >删除</ElButton
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </div>
                        </ElFormItem>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <div class="fixed right-4 top-[93px] z-[22]">
        <ElButton type="primary" class="!rounded-full w-[100px] !h-10" @click="opeCaseImage"> 优秀案例 </ElButton>
    </div>
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
import { FashionImageTypeEnum } from "../_enums";
import CaseImageV2 from "./case-image-v2.vue";

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
const maxSize = 20;
const ratioSize = [2, 1];

const getUploadImage = (result: any, type: string) => {
    const uri = result.data.uri;
    if (formData.type == FashionImageTypeEnum.DRESS) {
        formData.upper_clothes = uri;
        formData.lower_clothes = formData.upper_clothes;
        return;
    }
    formData[type] = uri;
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
        theme: "dark",
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
let render;
const DefineTemplate = {
    setup(_, { slots }) {
        return () => {
            render = slots.default;
        };
    },
};

const UploadTemplate = (props) => {
    return render(props);
};

// 模板渲染 End

watchEffect(() => {
    emit("update:formData", formData);
});

defineExpose({
    getFormData: () => {
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
            if (!formData.upper_clothes) {
                feedback.msgWarning("请上传上衣");
                reject(false);
                return;
            }
            if (formData.type == FashionImageTypeEnum.UPPER_LOWER_CLOTHES) {
                if (!formData.lower_clothes) {
                    feedback.msgWarning("请上传下装");
                    reject(false);
                    return;
                }
            }

            if (formData.persons.length == 0) {
                feedback.msgWarning("请选择模特");
                reject(false);
                return;
            }
            resolve(true);
        });
    },
});
</script>
