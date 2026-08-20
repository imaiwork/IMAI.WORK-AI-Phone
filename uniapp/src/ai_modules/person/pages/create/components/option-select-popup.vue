<template>
    <popup-bottom
        v-model="show"
        :title="title"
        :height="height"
        border-radius="44"
        custom-class="bg-white"
        :z-index="5001"
        :mask-close-able="true">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 pt-[24rpx]" :class="mode === 'grid' ? 'px-[36rpx]' : 'px-[32rpx]'">
                    <scroll-view scroll-y class="h-full">
                        <view v-if="mode === 'grid'" class="grid grid-cols-3 gap-[18rpx] pb-[100rpx]">
                            <view
                                v-for="item in options"
                                :key="item.name"
                                class="relative h-[84rpx] rounded-[22rpx] flex items-center justify-center border-[2rpx]"
                                :class="
                                    isOptionSelected(item.name)
                                        ? 'bg-[#EAF2FF] border-primary'
                                        : 'bg-[#F7F9FC] border-[transparent]'
                                "
                                @click="selectOption(item.name)">
                                <text class="text-[26rpx] mr-[8rpx]">{{ item.emoji }}</text>
                                <text
                                    class="text-[24rpx] font-bold"
                                    :class="isOptionSelected(item.name) ? 'text-primary' : 'text-[#4B5563]'">
                                    {{ item.name }}
                                </text>
                                <view
                                    v-if="multiple && isOptionSelected(item.name)"
                                    class="absolute top-[6rpx] right-[6rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                    <u-icon name="checkmark" color="#ffffff" size="18"></u-icon>
                                </view>
                            </view>
                        </view>

                        <view class="pb-[100rpx]" v-else>
                            <view
                                v-for="item in options"
                                :key="item.name"
                                class="rounded-[22rpx] px-[22rpx] py-[20rpx] mb-[16rpx] flex items-center border-[2rpx]"
                                :class="
                                    localSelectedValue === item.name
                                        ? 'bg-[#EAF2FF] border-primary'
                                        : 'bg-[#F7F9FC] border-[transparent]'
                                "
                                @click="selectOption(item.name)">
                                <text class="text-[34rpx] mr-[18rpx]">{{ item.emoji }}</text>
                                <view class="flex-1">
                                    <text class="block text-[28rpx] font-black text-[#111827]">{{ item.name }}</text>
                                    <text
                                        v-if="item.desc"
                                        class="block text-[23rpx] text-[#7B8798] leading-[1.5] mt-[4rpx]">
                                        {{ item.desc }}
                                    </text>
                                </view>
                                <view
                                    class="w-[32rpx] h-[32rpx] rounded-full border-[3rpx] flex items-center justify-center ml-[16rpx]"
                                    :class="localSelectedValue === item.name ? 'border-primary' : 'border-[#CDD6E4]'">
                                    <view
                                        v-if="localSelectedValue === item.name"
                                        class="w-[16rpx] h-[16rpx] rounded-full bg-primary"></view>
                                </view>
                            </view>

                            <view
                                v-if="customEnabled"
                                class="rounded-[22rpx] px-[22rpx] py-[20rpx] mb-[16rpx] flex items-center border-[2rpx]"
                                :class="
                                    localCustomVisible
                                        ? 'bg-[#EAF2FF] border-primary'
                                        : 'bg-[#F7F9FC] border-[transparent]'
                                "
                                @click="openCustom">
                                <text class="text-[34rpx] mr-[18rpx]">✏️</text>
                                <view class="flex-1">
                                    <text class="block text-[28rpx] font-black text-[#111827]">自定义</text>
                                    <text class="block text-[23rpx] text-[#7B8798] leading-[1.5] mt-[4rpx]">
                                        {{ customDesc }}
                                    </text>
                                </view>
                            </view>

                            <view
                                v-if="customEnabled && localCustomVisible"
                                class="rounded-[24rpx] bg-[#F0F4FA] px-[24rpx] py-[6rpx] mb-[20rpx]">
                                <u-input
                                    v-model="localCustomValue"
                                    :maxlength="customMaxlength"
                                    clearable
                                    :placeholder="customPlaceholder"
                                    :custom-style="{
                                        fontSize: '26rpx',
                                        color: '#111827',
                                        fontWeight: '600',
                                    }"
                                    placeholder-style="color:#B8C3D6;font-size:26rpx;font-weight:700;"
                                    :border="false" />
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view
                    v-if="mode === 'grid'"
                    class="px-[36rpx] pt-[20rpx] pb-[28rpx] border-[0] border-t border-solid border-[#F0F3F8] shrink-0">
                    <view class="flex items-center justify-between mb-[18rpx]">
                        <view v-if="customEnabled" class="flex items-center" @click="toggleCustom">
                            <u-icon name="plus-circle" color="#9CA3AF" size="26"></u-icon>
                            <text class="ml-[10rpx] text-[24rpx] text-[#9CA3AF] font-bold">{{ customToggleText }}</text>
                        </view>
                        <view v-else></view>
                        <view class="flex items-center">
                            <text
                                v-if="multiple && selectedCount"
                                class="mr-[16rpx] text-[24rpx] text-primary font-bold"
                                >已选 {{ selectedCount }}{{ maxSelected > 0 ? `/${maxSelected}` : "" }} 项</text
                            >
                            <view
                                class="h-[72rpx] px-[40rpx] rounded-full bg-primary flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(47,115,246,0.25)]"
                                @click="confirmSelection">
                                <text class="text-white text-[26rpx] font-black">{{ confirmText }}</text>
                            </view>
                        </view>
                    </view>
                    <template v-if="customEnabled && localCustomVisible">
                        <template v-if="multiple">
                            <view
                                v-for="(item, index) in localCustomValues"
                                :key="index"
                                class="rounded-[24rpx] bg-[#F0F4FA] px-[24rpx] py-[6rpx] mb-[12rpx] flex items-center">
                                <view class="flex-1">
                                    <u-input
                                        v-model="localCustomValues[index]"
                                        :maxlength="customMaxlength"
                                        :placeholder="customPlaceholder"
                                        :custom-style="{
                                            fontSize: '26rpx',
                                            color: '#111827',
                                            fontWeight: '600',
                                        }"
                                        placeholder-style="color:#B8C3D6;font-size:26rpx;font-weight:700;"
                                        :border="false" />
                                </view>
                                <view
                                    class="ml-[12rpx] w-[40rpx] h-[40rpx] flex items-center justify-center"
                                    @click="removeCustomEntry(index)">
                                    <u-icon name="close-circle-fill" color="#C0CAD9" size="34"></u-icon>
                                </view>
                            </view>
                            <view
                                v-if="canAddCustom"
                                class="h-[64rpx] rounded-[24rpx] border-[2rpx] border-dashed border-[#C7D6EE] flex items-center justify-center"
                                @click="addCustomEntry">
                                <u-icon name="plus" color="#2F73F6" size="22"></u-icon>
                                <text class="ml-[8rpx] text-[24rpx] text-primary font-bold">继续添加</text>
                            </view>
                        </template>
                        <view v-else class="rounded-[24rpx] bg-[#F0F4FA] px-[24rpx] py-[6rpx]">
                            <u-input
                                v-model="localCustomValue"
                                :maxlength="customMaxlength"
                                clearable
                                :placeholder="customPlaceholder"
                                :custom-style="{
                                    fontSize: '26rpx',
                                    color: '#111827',
                                    fontWeight: '600',
                                }"
                                placeholder-style="color:#B8C3D6;font-size:26rpx;font-weight:700;"
                                :border="false" />
                        </view>
                    </template>
                </view>

                <view v-else class="px-[32rpx] py-[24rpx] shrink-0">
                    <view
                        class="h-[86rpx] rounded-[26rpx] bg-primary flex items-center justify-center shadow-[0_8rpx_28rpx_rgba(47,115,246,0.28)]"
                        @click="confirmSelection">
                        <text class="text-white text-[28rpx] font-black">{{ confirmText }}</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
interface SelectOption {
    name: string;
    emoji: string;
    desc?: string;
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        height?: string;
        mode?: "grid" | "list";
        options: SelectOption[];
        selectedValue: string | string[];
        confirmText?: string;
        customEnabled?: boolean;
        customToggleText?: string;
        customDesc?: string;
        customPlaceholder?: string;
        customMaxlength?: number;
        /** 是否多选；为 true 时 selectedValue 应为 string[] */
        multiple?: boolean;
        /** 多选最大数量；0 表示不限制 */
        maxSelected?: number;
    }>(),
    {
        height: "80%",
        mode: "list",
        confirmText: "确认选择",
        customEnabled: false,
        customToggleText: "没找到？手动填写",
        customDesc: "自己描述风格，AI 按你要求生成文案和私信回复话术",
        customPlaceholder: "",
        customMaxlength: 80,
        multiple: false,
        maxSelected: 0,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "update:selectedValue", value: string | string[]): void;
    (event: "confirm", value: string | string[]): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const localSelectedValue = ref("");
const localSelectedValues = ref<string[]>([]);
const localCustomVisible = ref(false);
const localCustomValue = ref("");
// 多选模式下支持填写多个自定义项，单选模式仍用 localCustomValue
const localCustomValues = ref<string[]>([]);

const isOptionSelected = (name: string): boolean => {
    return props.multiple ? localSelectedValues.value.includes(name) : localSelectedValue.value === name;
};

// 自定义项中有有效内容的条目数，每条占 1 个名额
const customCount = computed(() => {
    if (!props.multiple || !localCustomVisible.value) return 0;
    return localCustomValues.value.filter((value) => value.trim()).length;
});
// 已选总数 = 预设选中项 + 自定义项，作为名额校验与文案展示的唯一口径
const selectedCount = computed(() => localSelectedValues.value.length + customCount.value);
// 还有名额，且最后一条已填写时，才允许继续添加新的自定义项
const canAddCustom = computed(() => {
    if (!props.multiple) return false;
    const hasRoom = props.maxSelected <= 0 || selectedCount.value < props.maxSelected;
    const last = localCustomValues.value[localCustomValues.value.length - 1];
    const lastFilled = localCustomValues.value.length === 0 || Boolean(last && last.trim());
    return hasRoom && lastFilled;
});

const syncFromProps = (): void => {
    if (props.multiple) {
        const incoming = Array.isArray(props.selectedValue)
            ? [...props.selectedValue]
            : props.selectedValue
            ? [String(props.selectedValue)]
            : [];
        // 多选下，预设之外的项视为自定义输入（仅当开启自定义时）；
        // 自定义项只保留在 localCustomValue，不再混入 localSelectedValues，避免名额被重复计数
        const presetSet = new Set(props.options.map((item) => item.name));
        const extras = incoming.filter((value) => !presetSet.has(value));
        localSelectedValues.value = incoming.filter((value) => presetSet.has(value));
        localCustomVisible.value = Boolean(props.customEnabled && extras.length);
        localCustomValues.value = extras.length ? [...extras] : [];
        return;
    }

    const incoming = Array.isArray(props.selectedValue) ? props.selectedValue[0] || "" : props.selectedValue || "";
    localSelectedValue.value = incoming;
    const isPresetOption = props.options.some((item) => item.name === incoming);
    localCustomVisible.value = Boolean(props.customEnabled && incoming && !isPresetOption);
    localCustomValue.value = localCustomVisible.value ? incoming : "";
};

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) syncFromProps();
    },
);

const toggleCustom = (): void => {
    localCustomVisible.value = !localCustomVisible.value;
    if (props.multiple) {
        if (localCustomVisible.value) {
            if (!localCustomValues.value.length) localCustomValues.value = [""];
        } else {
            localCustomValues.value = [];
        }
        return;
    }
    if (!localCustomVisible.value) {
        localCustomValue.value = "";
    } else {
        localSelectedValue.value = "";
    }
};

const addCustomEntry = (): void => {
    if (!canAddCustom.value) return;
    localCustomValues.value.push("");
};

const removeCustomEntry = (index: number): void => {
    localCustomValues.value.splice(index, 1);
    if (!localCustomValues.value.length) localCustomVisible.value = false;
};

const openCustom = (): void => {
    localCustomVisible.value = true;
    if (!props.multiple) {
        localSelectedValue.value = "";
    }
};

const selectOption = (value: string): void => {
    if (props.multiple) {
        const index = localSelectedValues.value.indexOf(value);
        if (index >= 0) {
            localSelectedValues.value.splice(index, 1);
        } else {
            if (props.maxSelected > 0 && selectedCount.value >= props.maxSelected) {
                uni.showToast({ title: `最多选择 ${props.maxSelected} 项`, icon: "none" });
                return;
            }
            localSelectedValues.value.push(value);
        }
        return;
    }
    localSelectedValue.value = value;
    localCustomVisible.value = false;
    localCustomValue.value = "";
};

const confirmSelection = (): void => {
    if (props.multiple) {
        const finalValues = [...localSelectedValues.value];
        if (localCustomVisible.value) {
            localCustomValues.value.forEach((value) => {
                const customText = value.trim();
                if (customText && !finalValues.includes(customText)) finalValues.push(customText);
            });
        }
        if (props.maxSelected > 0 && finalValues.length > props.maxSelected) {
            uni.showToast({ title: `最多选择 ${props.maxSelected} 项`, icon: "none" });
            return;
        }
        if (finalValues.length) {
            emit("update:selectedValue", finalValues);
            emit("confirm", finalValues);
        }
        show.value = false;
        return;
    }

    const finalValue = localCustomVisible.value ? localCustomValue.value.trim() : localSelectedValue.value;
    if (finalValue) {
        emit("update:selectedValue", finalValue);
        emit("confirm", finalValue);
    }
    show.value = false;
};
</script>
