<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="flex justify-between items-center">
                <el-page-header :content="headerTitle" @back="$router.back()" />
                <el-button type="primary" @click="handleExample">一键填入示例数据</el-button>
            </div>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-end">
                <el-tooltip popper-class="!w-[500px]">
                    <div>
                        <el-button>查看字段说明</el-button>
                    </div>
                    <template #content>
                        <div class="flex flex-col gap-y-4">
                            <p>助理图标： 助理名称（原名称）：</p>
                            <p>助理简介（原简介）：</p>
                            <p>简要介绍这个助理的功能定位和核心用途，让用户一眼明白它能帮什么忙。</p>
                            <p>
                                助理人设（原指令）：设定助理的角色性格和行为逻辑，比如它像谁、语气如何、怎么回答、回答什么不回答什么。
                            </p>
                            <p>用户提示词（原表单词提问配置）：向模型提供用户指令，如查询或任何基于文本输入的提问。</p>
                            <p>助理开场白（原开场白）：当用户新建对话时助理自动发起的第一句话</p>
                            <p>助理分类（原所属类目）：决定了用户在哪个分类下可找到该助理</p>
                            <p>
                                用户填写参数（原填写表单）：用户需要主动填写的关键参数字段，这些信息将直接影响生成结果。适合用表单方式引导填写。
                            </p>
                        </div>
                    </template>
                </el-tooltip>
            </div>
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="120px">
                <el-form-item label="助理图标" prop="logo">
                    <material-picker v-model="formData.logo" :limit="1" />
                </el-form-item>
                <el-form-item label="助理名称" prop="name">
                    <div class="w-[380px]">
                        <el-input placeholder="请输入名称" v-model="formData.name" />
                    </div>
                </el-form-item>
                <el-form-item label="助理简介" prop="description">
                    <div class="w-[380px]">
                        <el-input placeholder="添加有关于此机器人的功能简短描述" v-model="formData.description" />
                    </div>
                </el-form-item>
                <el-form-item label="助理人设" prop="instructions">
                    <div class="w-[380px]">
                        <el-input
                            placeholder="此机器人能做些什么?它的行为是怎么样的？它应该避免些什么?"
                            :rows="8"
                            type="textarea"
                            v-model="formData.instructions" />
                    </div>
                </el-form-item>
                <el-form-item label="助理分类" prop="scene_id">
                    <div class="w-[380px]">
                        <el-cascader
                            v-model="formData.scene_id"
                            :options="optionsData.categoryList"
                            :props="{
                                value: 'id',
                                label: 'name',
                                children: 'sub_list',
                            }"
                            clearable
                            filterable
                            placeholder="请选择所属类目"
                            class="w-full">
                        </el-cascader>
                    </div>
                </el-form-item>
                <el-form-item label="用户提示词" prop="form_info" required>
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.form_info"
                            type="textarea"
                            :rows="6"
                            ref="elInputRef"
                            maxlength="2000"
                            resize="none"></el-input>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <div
                                v-for="item in formData.template_info.form"
                                @click="insertAFormField(item.props.field)">
                                <ElButton>{{ item.props.title }}</ElButton>
                            </div>
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="助理开场白">
                    <div class="w-[380px] flex flex-col gap-y-1">
                        <div class="w-full" v-for="(item, index) in formData.preliminary_ask" :key="index">
                            <el-input
                                v-model="item.value"
                                placeholder=""
                                maxlength="50"
                                @input="inputDol($event, index)">
                                <template #append>
                                    <div @click="delDol(index)">
                                        <Icon name="el-icon-Close"></Icon>
                                    </div>
                                </template>
                            </el-input>
                        </div>
                    </div>
                </el-form-item>

                <el-form-item label="填写表单" prop="template_info.form">
                    <div class="flex-1 min-w-0 max-w-[1000px]">
                        <div class="flex">
                            <el-button type="primary" @click="handleAdd">添加</el-button>
                        </div>
                        <div class="mt-4">
                            <el-table
                                ref="tableRef"
                                size="large"
                                class="mt-4"
                                row-key="id"
                                :data="formData.template_info.form"
                                v-draggable="draggableOptions">
                                <el-table-column width="50">
                                    <template #default>
                                        <div class="move-icon cursor-move">
                                            <Icon name="el-icon-Rank" />
                                        </div>
                                    </template>
                                </el-table-column>
                                <el-table-column label="字段值" show-overflow-tooltip>
                                    <template #default="{ row }">
                                        <span>{{ row.props.field }}</span>
                                    </template>
                                </el-table-column>
                                <el-table-column label="字段名称" prop="props[title]" show-overflow-tooltip />
                                <el-table-column
                                    label="占位文字"
                                    prop="props[placeholder]"
                                    show-overflow-tooltip></el-table-column>
                                <el-table-column label="填写类型" prop="title" />
                                <el-table-column label="是否必填" prop="props[isRequired]">
                                    <template #default="{ row }">
                                        <el-switch v-model="row.props.isRequired" />
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="120" fixed="right">
                                    <template #default="{ row, $index }">
                                        <el-button type="primary" link @click="handleEdit(row, $index)">
                                            编辑
                                        </el-button>
                                        <el-button type="primary" link @click="handleDelete($index)"> 删除 </el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input-number v-model="formData.sort" />
                        <div class="form-tips">默认为0，数值越大越排前面</div>
                    </div>
                </el-form-item>
                <el-form-item label="状态" prop="sort">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </el-card>
        <form-designer-popup ref="formDesignerRef" @add="handleFormAdd" @edit="handleFormEdit" />
        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>
    </div>
</template>
<script lang="ts" setup>
import { getWebsite } from "@/api/setting/website";
import type { FormInstance, FormRules, InputInstance } from "element-plus";
import { getAssistantCategoryList } from "@/api/ai_assistant/category";
import { useThrottleFn } from "@vueuse/core";
import feedback from "@/utils/feedback";
import { setRangeText } from "@/utils/dom";
import { useDictOptions } from "@/hooks/useDictOptions";

const formRef = shallowRef<FormInstance>();

const props = defineProps<{
    headerTitle: string;
    modelValue: Record<string, any>;
    isEdit?: boolean;
}>();
const emit = defineEmits<{
    (event: "update:modelValue", value: Record<string, any>): void;
    (event: "submit"): void;
}>();

const formData = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});
const tableRef = shallowRef();

const draggableOptions = [
    {
        selector: "tbody",
        options: {
            animation: 150,
            handle: ".move-icon",
            onEnd: ({ newIndex, oldIndex }: any) => {
                const arr = formData.value.template_info.form;
                const currRow = arr.splice(oldIndex, 1)[0];
                arr.splice(newIndex, 0, currRow);
                formData.value.form = [];
                nextTick(() => {
                    formData.value.template_info.form = arr;
                });
            },
        },
    },
];

const elInputRef = shallowRef<InputInstance>();
const insertAFormField = (field: string) => {
    formData.value.form_info = setRangeText(elInputRef.value?.textarea!, `\${${field}}`);
};

const inputDol = (e: any, index: number) => {
    if (formData.value.preliminary_ask.length > 5) return;
    const laseIndex = formData.value.preliminary_ask.length - 1;
    if (formData.value.preliminary_ask.length === 1 && !formData.value.preliminary_ask[0].value) return;
    if (formData.value.preliminary_ask[index].value && formData.value.preliminary_ask[laseIndex].value != "") {
        formData.value.preliminary_ask.push({ value: "" });
    } else if (!formData.value.preliminary_ask[index].value) {
        delDol(index);
    }
};

const delDol = (index: number) => {
    if (formData.value.preliminary_ask.length === 1) return;
    const laseIndex = formData.value.preliminary_ask.length - 1;
    if (formData.value.preliminary_ask[laseIndex].value != "") {
        formData.value.preliminary_ask.push({ value: "" });
    }
    formData.value.preliminary_ask.splice(index, 1);
};

//表单校验规则
const rules: FormRules = {
    name: [
        {
            required: true,
            message: "请输入名称",
        },
    ],
    description: [
        {
            required: true,
            message: "请输入助理简介",
        },
    ],
    instructions: [
        {
            required: true,
            message: "请输入全局指令",
        },
    ],
    form_info: [
        {
            required: true,
            message: "请配置表单词提问",
        },
    ],
    scene_id: [
        {
            required: true,
            message: "请选择所属类目",
        },
    ],
    logo: [
        {
            required: true,
            message: "请选择模型图标",
        },
    ],
};
const formDesignerRef = shallowRef();
//添加
const handleAdd = async () => {
    await nextTick();
    formDesignerRef.value?.open("add");
};
const handleFormAdd = useThrottleFn((value: any) => {
    const isSameField = !!formData.value.template_info.form.find((item: any) => item.props.field === value.props.field);
    const isSameType = !!formData.value.template_info.form.find((item: any) => item.name === "WidgetFile");
    if (isSameType) {
        return feedback.msgError("只能添加一个上传文件字段");
    }
    if (isSameField) {
        return feedback.msgError("字段值重复，请修改字段值");
    }

    formData.value.template_info.form.push(value);
    formDesignerRef.value?.close();
});
const currentIndex = ref(0);
const handleEdit = (value: any, index: number) => {
    currentIndex.value = index;
    formDesignerRef.value?.open("edit");
    formDesignerRef.value?.setFormData(value);
};

const handleDelete = async (index: number) => {
    await feedback.confirm("确定删除当前项？");
    formData.value.template_info.form.splice(index, 1);
};

const handleFormEdit = useThrottleFn((value: any) => {
    formData.value.template_info.form[currentIndex.value] = value;
    formDesignerRef.value?.close();
});

const { optionsData } = useDictOptions<{ categoryList: any[]; kbList: any[] }>({
    categoryList: {
        api: getAssistantCategoryList,
        transformData(data) {
            return data.lists;
        },
    },
});

//提交
const handleSave = async () => {
    await formRef.value?.validate();
    emit("submit");
};

const handleExample = async () => {
    await feedback.confirm("确定一键填入示例数据？");
    const data = await getWebsite();
    formData.value.logo = data.pc_logo;
    formData.value.name = "AI创业文案助理";
    formData.value.description =
        "我是你的AI创业文案助理，专为创业者和内容运营打造。无论你在构思产品介绍、广告文案，还是品牌口号，我都能快速生成专业又打动人心的文字内容，助你高效推广产品、打磨品牌语言。";
    formData.value.instructions =
        "人设：灵感型创意合伙人，语言富有感染力，节奏感强，理解用户需求快速出击。恢复逻辑：当用户说“重新写一版”或“换个方向”，系统将清空当前创意并重新生成，同时记录前一版本以供回溯。";
    formData.value.preliminary_ask = [
        {
            value: "嗨，欢迎使用AI创业文案助理！告诉我你的产品、用户和推广场景，我将帮你生成打动人心的文案，让你的创意一语中的",
        },
        { value: "" },
    ];
    const formFields = [
        {
            name: "WidgetInput",
            title: "单行文本",
            id: "mc4o2en3",
            props: {
                field: "input1",
                title: "品牌名",
                placeholder: "",
                maxlength: 200,
                isRequired: false,
            },
        },
        {
            name: "WidgetInput",
            title: "单行文本",
            id: "mc4o2en3",
            props: {
                field: "input2",
                title: "产品类型",
                placeholder: "",
                maxlength: 200,
                isRequired: false,
            },
        },
        {
            name: "WidgetInput",
            title: "单行文本",
            id: "mc4o2en3",
            props: {
                field: "input3",
                title: "目标用户推广场景",
                placeholder: "",
                maxlength: 200,
                isRequired: false,
            },
        },
        {
            name: "WidgetInput",
            title: "单行文本",
            id: "mc4o2en3",
            props: {
                field: "input4",
                title: "希望语气",
                placeholder: "",
                maxlength: 200,
                isRequired: false,
            },
        },
    ];

    formData.value.template_info.form = formFields;
    formData.value.form_info = `用户提供给你的参数是：${formFields
        .filter((item) => item.props)
        .map((item) => `${item.props.title}\${${item.props.field}}`)
        .join("；")}`;
};
</script>
