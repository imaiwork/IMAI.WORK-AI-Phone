<template>
    <div class="chat-message-item flex gap-x-2">
        <!-- My message -->
        <div v-if="type == 1" class="ml-auto flex flex-col">
            <div class="flex flex-col gap-2 items-end" v-if="fileLists.length > 0">
                <div v-for="(file, index) in fileLists" class="relative group">
                    <file-card :uid="file.uid || index" :name="file.name" :size="file.size" :url="file.url" />
                    <div
                        class="rounded-lg absolute top-0 left-0 w-full h-full group-hover:visible invisible bg-[rgba(0,0,0,0.4)] cursor-pointer flex items-center justify-center">
                        <div>
                            <ElTooltip content="查看">
                                <a :href="file.url" target="_blank"
                                    ><Icon name="el-icon-View" size="18" color="#ffffff"></Icon
                                ></a>
                            </ElTooltip>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative group mt-2" @mouseenter="showMyContent = true" @mouseleave="handleMyMessageMouseLeave">
                <div class="message-contain message-contain--my" ref="messageMyRef" v-if="message">
                    <slot name="my"></slot>
                </div>
                <div
                    class="mt-2 flex items-center justify-end cursor-pointer gap-2"
                    :class="{ invisible: !showMyContent && !showMyCopyPopover }"
                    @mouseenter="showMyContent = true">
                    <ElTooltip v-if="showShare" content="分享">
                        <div
                            class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                            @click.stop="handleShare">
                            <Icon name="local-icon-share" :size="16"></Icon>
                        </div>
                    </ElTooltip>
                    <ElPopover
                        v-if="showCopy"
                        popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                        :show-arrow="false"
                        @hide="handleHideMyCopy"
                        @show="handleShowMyCopy">
                        <template #reference>
                            <div
                                class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md flex items-center gap-1"
                                @mouseenter="showMyContent = true"
                                @click.stop="copyText('my')">
                                <Icon
                                    :name="isCopying === 'my' ? 'local-icon-check' : 'local-icon-copy'"
                                    :size="16"></Icon>
                                <div
                                    style="transition: transform 0.3s ease"
                                    :class="{
                                        '-rotate-[180deg]': showMyCopyPopover,
                                    }">
                                    <Icon name="el-icon-ArrowDown" :size="12"></Icon>
                                </div>
                            </div>
                        </template>
                        <div>
                            <div class="table-action-item" @click.stop="copyMarkdown('my')">复制为 Markdown</div>
                            <div class="table-action-item" @click.stop="copyText('my')">复制为纯文本</div>
                        </div>
                    </ElPopover>
                    <ElTooltip v-if="showQuote" content="引用">
                        <div
                            class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                            @click.stop="handleQuote">
                            <Icon name="local-icon-double_quotes_l" :size="16"></Icon>
                        </div>
                    </ElTooltip>
                    <ElTooltip v-if="showEdit" content="编辑">
                        <div
                            class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                            @click.stop="handleEdit">
                            <Icon name="local-icon-pencil" :size="16"></Icon>
                        </div>
                    </ElTooltip>
                </div>
            </div>
        </div>

        <!-- Avatar -->
        <div class="flex-shrink-0">
            <img v-if="avatar" :src="avatar" class="w-[48px] h-[48px] rounded-full object-cover" />
            <div class="w-[48px] h-[48px]" v-else>
                <img src="@/assets/images/chat_logo.png" />
            </div>
        </div>

        <!-- His message -->
        <div class="" v-if="type == 2">
            <div class="message-contain message-contain--his flex flex-col">
                <div>
                    <slot name="rob"></slot>
                </div>
                <div class="chat-loader mt-2" v-if="loading"></div>
                <template v-else>
                    <div class="flex items-center gap-2 mt-4">
                        <!-- <ElTooltip content="重新生成">
                            <div
                                class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                                @click="emit('regenerate')">
                                <Icon name="local-icon-reset" :size="16"></Icon>
                            </div>
                        </ElTooltip> -->
                        <ElTooltip v-if="showShare" content="分享">
                            <div
                                class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                                @click.stop="handleShare">
                                <Icon name="local-icon-share" :size="16"></Icon>
                            </div>
                        </ElTooltip>
                        <ElPopover
                            v-if="showCopy"
                            popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                            :show-arrow="false"
                            @hide="showCopyPopover = false"
                            @show="showCopyPopover = true">
                            <template #reference>
                                <div
                                    class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md group flex items-center gap-1"
                                    @click.stop="copyText('his')">
                                    <Icon
                                        :name="isCopying === 'his' ? 'local-icon-check' : 'local-icon-copy'"
                                        :size="16"></Icon>
                                    <div
                                        class="group-hover:-rotate-[180deg] transition-all duration-300"
                                        :class="{
                                            '-rotate-[180deg]': showCopyPopover,
                                        }">
                                        <Icon name="el-icon-ArrowDown" :size="12"></Icon>
                                    </div>
                                </div>
                            </template>
                            <div>
                                <div class="table-action-item" @click.stop="copyMarkdown('his')">复制为 Markdown</div>
                                <div class="table-action-item" @click.stop="copyText('his')">复制为纯文本</div>
                            </div>
                        </ElPopover>
                        <ElTooltip v-if="showQuote" content="引用">
                            <div
                                class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                                @click.stop="handleQuote">
                                <Icon name="local-icon-double_quotes_l" :size="16"></Icon>
                            </div>
                        </ElTooltip>
                        <ElTooltip v-if="showEdit" content="编辑">
                            <div
                                class="leading-[0] cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-md"
                                @click.stop="handleEdit">
                                <Icon name="local-icon-pencil" :size="16"></Icon>
                            </div>
                        </ElTooltip>
                    </div>
                </template>
            </div>
            <div class="ml-[10px]">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import FileCard from "../chatting/file-card/index.vue";

const props = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    stopping: {
        type: Boolean,
        default: false,
    },
    avatar: {
        type: String,
        default: "",
    },
    message: {
        type: String,
        default: "",
    },
    type: {
        type: Number,
        default: null,
    },
    showCopy: {
        type: Boolean,
        default: true,
    },
    showEdit: {
        type: Boolean,
        default: false,
    },
    showQuote: {
        type: Boolean,
        default: false,
    },
    showShare: {
        type: Boolean,
        default: false,
    },
    fileLists: {
        type: Array<{ uid?: string; name: string; type: string; size: number | string; url: string }>,
        default: [],
    },
    consumeTokens: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits<{
    (e: "copyContent", format: "markdown" | "text"): void;
    (e: "copyMyContent", format: "markdown" | "text"): void;
    (e: "regenerate"): void;
    (e: "share"): void;
    (e: "edit"): void;
    (e: "quote"): void;
}>();

const showMyContent = ref(false);
const showCopyPopover = ref(false);
const showMyCopyPopover = ref(false);
const isCopying = ref<"my" | "his" | null>(null);

const handleCopy = (type: "my" | "his", format?: "markdown" | "text") => {
    if (isCopying.value) return;
    isCopying.value = type;
    setTimeout(() => {
        isCopying.value = null;
    }, 1000);
    // @ts-ignore
    emit(type === "my" ? "copyMyContent" : "copyContent", format);
};

const copyMarkdown = (type: "my" | "his") => handleCopy(type, "markdown");
const copyText = (type: "my" | "his") => handleCopy(type, "text");

const handleShare = (e: Event) => {
    emit("share");
};

const handleEdit = (e: Event) => {
    emit("edit");
};

const handleQuote = (e: Event) => {
    emit("quote");
};

const handleMyMessageMouseLeave = () => {
    if (!showMyCopyPopover.value) {
        showMyContent.value = false;
    }
};

const handleHideMyCopy = () => {
    showMyCopyPopover.value = false;
    setTimeout(() => {
        if (!showMyCopyPopover.value) {
            showMyContent.value = false;
        }
    }, 100);
};

const handleShowMyCopy = () => {
    showMyCopyPopover.value = true;
    showMyContent.value = true;
};
</script>

<style lang="scss" scoped>
.chat-message-item {
    // display: flex;
    flex: 1;
    min-width: 0;
    font-size: 15px;
    .message-avatar {
        min-width: 40px;
    }

    .message-contain {
        max-width: 770px;
    }

    .message-contain--my {
        @apply bg-[#edf3fe]  ml-auto rounded-2xl px-4 py-3 w-fit;
    }

    .message-contain--his {
        @apply min-w-0 relative rounded-tr-2xl rounded-bl-2xl rounded-br-2xl p-4;
    }
}
</style>
