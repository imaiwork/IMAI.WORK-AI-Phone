<template>
    <div class="h-full flex flex-col">
        <div class="flex justify-between gap-x-2 px-[14px]">
            <div>
                <div class="text-white text-[11px]">{{ typeName }}</div>
                <div class="flex items-center gap-x-2 mt-2">
                    <span class="text-[#ffffff80] text-[11px]">
                        共{{ valueList.length }}个{{ typeName }}，已配置{{ count }}个
                    </span>
                    <ElTooltip
                        v-if="publishTypeName"
                        placement="top"
                        popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2"
                        :show-arrow="false">
                        <div
                            class="w-4 h-4 rounded-full flex items-center justify-center shadow-[0_0_0_1px_rgba(255,255,255,0.2)] cursor-pointer">
                            <Icon name="local-icon-tips2" color="#ffffff" :size="16"></Icon>
                        </div>
                        <template #content>
                            <div class="text-[#ffffff80] text-[11px] leading-6 w-[212px]">
                                1.如配置{{ typeName }}数量等于{{ publishTypeName }}数量，将按{{
                                    publishTypeName
                                }}顺序匹配{{ typeName }}。 <br />
                                2.如配置{{ typeName }}数量不等于{{ publishTypeName }}数量，将{{
                                    typeName
                                }}随机匹配给各{{ publishTypeName }}。
                            </div>
                        </template>
                    </ElTooltip>
                </div>
            </div>
            <div>
                <ElButton class="!h-10 w-[106px] !border-[#ffffff1a]" color="#262626" @click="handleAdd"
                    >添加{{ typeName }}</ElButton
                >
            </div>
        </div>
        <div class="grow min-h-0 mt-[14px]">
            <ElScrollbar>
                <div class="flex flex-col gap-y-2 px-[14px]">
                    <template v-if="type == 1">
                        <div
                            v-for="(item, index) in valueList"
                            class="flex items-center gap-x-2 border border-app-border-2 px-3 h-11 rounded-md"
                            :key="index">
                            <div
                                class="rounded-[100px] text-white min-w-[32px] h-5 flex items-center justify-center"
                                :class="[
                                    item.content ? 'bg-[#3BB840]' : 'shadow-[0_0_0_1px_var(--app-border-color-2)]',
                                ]">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <ElInput
                                    v-model="item.content"
                                    class="!h-10"
                                    maxlength="20"
                                    show-word-limit
                                    input-style="font-size: 11px"
                                    placeholder="-"
                                    @blur="handleBlur(index)" />
                            </div>
                            <div class="w-[1px] h-[12px] bg-[#ffffff1a]"></div>
                            <div>
                                <div class="w-4 h-4" @click="handleDelete(index)">
                                    <close-btn :icon-size="10" :theme="ThemeEnum.DARK"></close-btn>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-if="type == 2 || type == 3">
                        <div
                            v-for="(item, index) in valueList"
                            class="border border-app-border-2 p-3 rounded-md h-fit"
                            :key="index">
                            <div class="flex justify-between gap-x-2">
                                <div
                                    class="rounded-[100px] text-white min-w-[32px] h-5 flex items-center justify-center"
                                    :class="[
                                        item.content ? 'bg-[#3BB840]' : 'shadow-[0_0_0_1px_var(--app-border-color-2)]',
                                    ]">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <div class="w-4 h-4" @click="handleDelete(index)">
                                        <close-btn :icon-size="10" :theme="ThemeEnum.DARK"></close-btn>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <ElInput
                                    v-model="item.content"
                                    maxlength="800"
                                    show-word-limit
                                    placeholder="请输入内容"
                                    type="textarea"
                                    resize="none"
                                    input-style="font-size: 11px"
                                    :rows="6"
                                    @blur="handleBlur(index)" />
                            </div>
                            <div class="relative mt-4 flex gap-x-2 items-end" v-if="showTopic">
                                <div class="flex-1 flex flex-wrap gap-2">
                                    <div
                                        v-for="(topic, t_index) in item.topic"
                                        :key="t_index"
                                        class="relative text-[11px] rounded-md shadow-[0_0_0_1px_rgba(255,255,255,0.1)]"
                                        :class="{ 'bg-app-bg-4': topic }">
                                        <ElInput
                                            v-model="item.topic[t_index]"
                                            class="h-[26px] !w-[78px]"
                                            placeholder="#请输入话题"
                                            input-style="font-size: 11px;height: 100%"
                                            @blur="handleTopicBlur(index, t_index)"></ElInput>
                                        <div
                                            class="absolute -top-2 -right-2 w-4 h-4 rounded-full flex items-center justify-center bg-[#FF3C26] cursor-pointer"
                                            @click="handleDeleteTopic(index, t_index)">
                                            <Icon name="local-icon-close" color="#ffffff"></Icon>
                                        </div>
                                    </div>
                                </div>
                                <div class="">
                                    <ElButton type="primary" class="!h-[26px]" @click="handleAddTopic(index)"
                                        >添加话题</ElButton
                                    >
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ThemeEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        type: 1 | 2 | 3;
        modelValue: any[];
        publishTypeName?: string;
        showTopic?: boolean;
    }>(),
    {
        showTopic: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: any): void;
}>();

const valueList = defineModel<any[]>("modelValue");

const count = computed(() => {
    return valueList.value.filter((item) => item.content).length;
});

const typeName = computed(() => {
    const types = {
        1: "标题",
        2: "正文描述",
        3: "口播文案",
    };
    return types[props.type];
});

const handleAdd = () => {
    if (props.type == 2) {
        valueList.value.push({
            content: "",
            topic: [],
        });
    } else {
        valueList.value.push({
            content: "",
        });
    }
    emit("update:modelValue", valueList.value);
};

const handleDelete = (index: number) => {
    useNuxtApp().$confirm({
        message: `确定要删除该${props.type == 1 ? "标题" : "描述"}吗？`,
        theme: "dark",
        onConfirm: () => {
            valueList.value.splice(index, 1);
            emit("update:modelValue", valueList.value);
        },
    });
};

const handleBlur = (index: number) => {
    emit("update:modelValue", valueList.value);
};

const handleAddTopic = (index: number) => {
    if (!valueList.value[index].topic) {
        valueList.value[index].topic = [];
    }
    valueList.value[index].topic.push("");
    emit("update:modelValue", valueList.value);
};

const handleDeleteTopic = (index: number, t_index: number) => {
    useNuxtApp().$confirm({
        message: `确定要删除该话题吗？`,
        theme: "dark",
        onConfirm: () => {
            valueList.value[index].topic.splice(t_index, 1);
            emit("update:modelValue", valueList.value);
        },
    });
};

const handleTopicBlur = (index: number, t_index: number) => {
    emit("update:modelValue", valueList.value);
};
</script>

<style scoped></style>
