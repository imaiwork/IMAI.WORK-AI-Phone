<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div class="h-full bg-white rounded-[32px] border border-br flex flex-col overflow-hidden">
            <div class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-br">
                <div class="group flex items-center gap-3 cursor-pointer" @click="closePanel">
                    <div
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 group-hover:bg-[#0065fb]/10 group-hover:text-primary transition-all">
                        <Icon name="el-icon-ArrowLeft" :size="16"></Icon>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-[#94A3B8] leading-none mb-1">
                            正在操作知识库：{{ knName }}
                        </div>
                        <div class="text-[14px] font-[900] text-[#1E293B]">返回上一步</div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="h-8 w-[1px] bg-[#F1F5F9]"></div>
                    <ElButton
                        type="primary"
                        class="!rounded-xl !h-11 !px-8 !font-black !bg-primary !border-none custom-shadow-primary"
                        :loading="isSubmitting"
                        @click="submitForm">
                        确认并提交
                    </ElButton>
                </div>
            </div>

            <ElScrollbar>
                <div class="p-8">
                    <div class="mb-8">
                        <h2 class="text-[18px] font-[900] text-[#0F172A] mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-5 bg-primary rounded-full"></span>
                            选择导入方式
                        </h2>
                        <div class="grid grid-cols-3 gap-5">
                            <div
                                v-for="item in typeTabs"
                                :key="item.type"
                                class="import-type-card"
                                :class="{ 'is-active': currentType == item.type }"
                                @click="handleTypeChange(item.type)">
                                <div class="flex items-start gap-4">
                                    <div class="icon-box" :class="{ 'is-active': currentType == item.type }">
                                        <Icon :name="item.icon" :size="24"></Icon>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[15px] font-[900] mb-1 leading-tight">{{ item.name }}</div>
                                        <div class="text-xs font-medium text-[#94A3B8] leading-relaxed">
                                            {{ item.desc }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-[#F1F5F9] flex items-center justify-between">
                                    <span class="text-[10px] font-black text-[#64748B] uppercase tracking-wider">
                                        {{ item.uploadType }}
                                    </span>
                                    <div
                                        v-show="currentType == item.type"
                                        class="w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                        <Icon name="el-icon-Check" color="white" :size="12" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-wrapper">
                        <template v-for="item in typeTabs" :key="item.type">
                            <Transition name="fade-slide">
                                <div v-show="item.type == currentType" class="dynamic-content">
                                    <component :is="item.component" v-model="item.data" />
                                </div>
                            </Transition>
                        </template>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>
<script setup lang="ts">
import { vectorKnowledgeBaseFileAdd } from "@/api/knowledge_base";
import type { IDataItem } from "./import/hook";
import Doc from "./import/doc.vue";
import Csv from "./import/csv.vue";
import Qa from "./import/qa.vue";
import Web from "./import/web.vue";

// ======================= 组件props和emits =======================

const props = withDefaults(
    defineProps<{
        knName: string | string[]; // 知识库名称

        knId: string | string[]; // 知识库ID
    }>(),

    {
        knName: "",

        knId: "",
    }
);

const emit = defineEmits<{
    (e: "back"): void; // 返回事件

    (e: "success"): void; // 提交成功事件
}>();

// ======================= 数据和状态管理 =======================

/**

* @description 定义导入类型的枚举

*/

enum TypeEnum {
    DOCUMENT = 1, // 通用文档

    QUESTION = 2, // 问答对

    AUTO_QUESTION = 3, // 自动拆分问答对

    WEB = 4, // 网页
}

/**

* @description 定义不同导入类型的标签页配置

* 每个对象包含类型、名称、描述、支持的文件类型、图标、对应的组件和数据模型

*/

const typeTabs = ref([
    {
        type: TypeEnum.DOCUMENT,

        name: "通用文档导入",

        desc: "选择文本文件，直接按其分段进行处理",

        uploadType: "支持 .txt , docx, .pdf, .md",

        icon: "local-icon-document2",

        component: markRaw(Doc),

        data: [] as IDataItem[],
    },

    {
        type: TypeEnum.QUESTION,

        name: "问答对导入",

        desc: "批量导入问答对，效果最佳",

        uploadType: "支持 .excel , csv",

        icon: "local-icon-word",

        component: markRaw(Csv),

        data: [] as IDataItem[],
    },

    {
        type: TypeEnum.WEB,

        name: "网页解析",

        desc: "输入网页链接，快速导入内容",

        uploadType: "支持 url链接",

        icon: "local-icon-sand_lock",

        component: markRaw(Web),

        data: [] as IDataItem[],
    },
]);

// 当前选中的导入类型，默认为通用文档

const currentType = ref<TypeEnum>(TypeEnum.DOCUMENT);

// ======================= 方法 =======================

/**

* @description 切换导入类型

* @param {TypeEnum} type - 目标类型

*/

const handleTypeChange = (type: TypeEnum) => {
    currentType.value = type;
};

/**

* @description 关闭当前面板，触发 back 事件

*/

const closePanel = () => {
    emit("back");
};

/**

* @description 提交表单

* 使用 useLockFn 防止重复提交

*/

const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
    const currentTab = typeTabs.value.find((item) => item.type == currentType.value);

    const isNull = currentTab.data.every((item) => !item.data.length);

    if (!currentTab || !currentTab.data.length || isNull) {
        feedback.msgWarning("请先添加数据");

        return;
    }

    try {
        await vectorKnowledgeBaseFileAdd({
            kb_id: props.knId as string,

            method: currentType.value,

            documents: currentTab.data,
        });

        feedback.msgSuccess("添加成功");

        emit("success");

        closePanel();
    } catch (error) {
        feedback.msgError(error as string);
    }
});

// ======================= 生命周期钩子 =======================

/**

* @description 处理页面刷新或关闭前的提示

*/

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    event.preventDefault();

    event.returnValue = "请勿刷新页面";

    return "请勿刷新页面";
};

onMounted(() => {
    window.addEventListener("beforeunload", handleBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", handleBeforeUnload);
});
</script>

<style scoped lang="scss">
.custom-shadow-primary {
    box-shadow: 0 8px 20px -6px rgba(0, 101, 251, 0.4);
}

.import-type-card {
    @apply p-5 rounded-[20px] bg-white border-2 border-[#F1F5F9] cursor-pointer transition-all duration-300 relative;

    &:hover {
        @apply translate-y-[-4px] border-[#0065fb]/30;
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.05);
    }

    &.is-active {
        @apply border-primary bg-[#0065fb]/[0.02];
        box-shadow: 0 12px 24px -8px rgba(0, 101, 251, 0.1);
    }
}

.icon-box {
    @apply w-12 h-12 rounded-xl bg-slate-50 border border-br flex items-center justify-center text-[#64748B] transition-all;

    &.is-active {
        @apply bg-primary text-white border-primary shadow-light shadow-[#0065fb]/20;
    }
}

.content-wrapper {
    @apply bg-slate-50 rounded-[24px] border border-[#F1F5F9] min-h-[400px] relative p-6;
}

.fade-slide-enter-active {
    transition: all 0.4s ease-out;
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(15px);
}

:deep(.el-upload) {
    .el-upload-dragger {
        @apply bg-white border-2 border-dashed border-br rounded-[20px] transition-all py-10;
        &:hover {
            @apply border-primary bg-[#0065fb]/[0.02];
        }
    }
}

:deep(.el-upload-list) {
    @apply mt-4 grid grid-cols-2 gap-3;
    .el-upload-list__item {
        @apply m-0 h-14 bg-white border border-br rounded-xl px-4 flex items-center shadow-[none] transition-all;
        &:hover {
            @apply border-[#0065fb]/40 shadow-light;
        }
    }
}
</style>
