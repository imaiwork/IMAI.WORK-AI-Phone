<script setup lang="ts">
import { computed } from "vue";
import { ElInput } from "element-plus";
const props = defineProps<{
    modelValue?: any[];
}>();
const emit = defineEmits<{
    (event: "update:modelValue", value: any[]): void;
}>();

const inputValue = ref("");
const inputVisible = ref(false);
const InputRef = ref<InstanceType<typeof ElInput>>();

const tagList: any = computed({
    get() {
        return props.modelValue || [];
    },
    set(value) {
        emit("update:modelValue", value!);
    },
});

const showInput = () => {
    inputVisible.value = true;
    nextTick(() => {
        InputRef.value!.input!.focus();
    });
};
const handleOptionClose = (tag: string) => {
    const list = tagList.value;
    list.splice(list.indexOf(tag), 1);
    tagList.value = list;
};
const handleInputConfirm = () => {
    const list = tagList.value;
    if (inputValue.value) {
        list.push(inputValue.value);
        tagList.value = list;
    }
    inputVisible.value = false;
    inputValue.value = "";
};

const tagIndex = ref<number>(-1);
const tagValue = ref<string>("");
const handleEdit = (index: number) => {
    tagIndex.value = index;
    tagValue.value = tagList.value[index];
};

const handleTagInputConfirm = () => {
    tagIndex.value = -1;
};
</script>
<template>
    <div class="flex items-center flex-wrap gap-2">
        <div v-for="(tag, index) in tagList" @click="handleEdit(index)">
            <ElTag
                v-if="tagIndex != index"
                :key="tag"
                closable
                :disable-transitions="true"
                @close="handleOptionClose(tag)">
                {{ tag }}
            </ElTag>
            <ElInput
                v-else
                v-model="tagList[index]"
                class="!w-28"
                ref="InputRef"
                size="small"
                maxlength="30"
                @keyup.enter="handleTagInputConfirm"
                @blur="handleTagInputConfirm" />
        </div>
        <ElInput
            class="!w-28"
            v-if="inputVisible"
            ref="InputRef"
            v-model="inputValue"
            size="small"
            maxlength="30"
            @keyup.enter="handleInputConfirm"
            @blur="handleInputConfirm" />
        <ElButton v-else size="small" @click="showInput"> + 添加 </ElButton>
    </div>
</template>
