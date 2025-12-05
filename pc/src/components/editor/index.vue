<template>
    <div class="h-full">
        <MdEditor
            class="h-full"
            v-model="value"
            :previewTheme="state.theme"
            :toolbars="toolbars"
            :toolbarsExclude="['github']"
            :preview="isPreview"
            @onUploadImg="onUploadImg">
            <template #defToolbars>
                <Emoji />
                <ExportPDF :modelValue="value" height="700px" @onSuccess="onSuccess" />
            </template>
        </MdEditor>
    </div>
</template>

<script setup lang="ts">
import { MdEditor, ToolbarNames } from "md-editor-v3";
import { ExportPDF, Emoji } from "@vavt/v3-extension";
import { uploadImage } from "@/api/app";

const props = withDefaults(
    defineProps<{
        modelValue: string | undefined | null;
        toolbars?: ToolbarNames[];
        isPreview?: boolean;
    }>(),
    {
        isPreview: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const value = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const state = reactive({
    theme: "mk-cute",
});

const toolbars = computed((): ToolbarNames[] => {
    return (
        props.toolbars || [
            "revoke",
            "next",
            "-",
            "bold",
            "underline",
            "italic",
            "strikeThrough",
            "-",
            "title",
            "sub",
            "sup",
            "quote",
            "unorderedList",
            "orderedList",
            "task",
            "codeRow",
            "code",
            "-",
            "link",
            "image",
            "table",
            "mermaid",
            "katex",
            "-",
            0,
            1,
            2,
            "=",
            "save",
            "prettier",
            "pageFullscreen",
            "catalog",
            "preview",
            "previewOnly",
            "htmlPreview",
            "github",
        ]
    );
});

const onUploadImg = async (file: File[], callback: (url: string) => void) => {
    const result = await Promise.all(
        file.map(async (file) => {
            const res = await uploadImage({ file });
            return res;
        })
    );
    // @ts-ignore
    callback(result.map((item) => ({ url: item.uri, alt: item.name })));
};

const onSuccess = (url: string) => {
    feedback.msgSuccess("导出成功");
};
</script>

<style lang="css">
@import "md-editor-v3/lib/style.css";
@import "@vavt/v3-extension/lib/asset/style.css";

.md-editor {
    height: 100%;
}
</style>
