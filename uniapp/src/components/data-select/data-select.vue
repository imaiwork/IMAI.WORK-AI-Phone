<template>
    <view class="uni-stat__select">
        <span v-if="label" class="uni-label-text hide-on-phone">{{ label + "：" }}</span>
        <view class="uni-stat-box" :class="{ 'uni-stat__actived': selectedItems.length > 0 }">
            <view class="uni-select" :class="{ 'uni-select--disabled': disabled }">
                <view class="uni-select__input-box" @click="toggleSelector">
                    <template v-if="selectedItems.length > 0">
                        <view v-if="multiple" class="flex-1 flex items-center flex-wrap gap-2">
                            <view
                                v-for="(item, index) in selectedItems"
                                class="bg-[#f4f4f5] px-2 rounded-lg text-[22rpx] flex items-center gap-1"
                                :key="index">
                                {{ item.text }}
                            </view>
                        </view>
                        <view class="flex-1" v-else>{{ selectedItems[0].text }}</view>
                    </template>
                    <view v-else class="uni-select__input-text uni-select__input-placeholder">
                        {{ typePlaceholder }}
                    </view>
                    <view
                        v-if="selectedItems.length > 0 && clear && !disabled"
                        @click.stop="clearVal"
                        class="leading-[0]">
                        <u-icon name="close" color="#c0c4cc" size="24" />
                    </view>
                    <view v-else>
                        <u-icon :name="showSelector ? 'arrow-up' : 'arrow-down'" size="24" color="#A1A1A1"></u-icon>
                    </view>
                </view>
                <view class="uni-select--mask" v-if="showSelector" @click="toggleSelector" />
                <view class="uni-select__selector" :style="getOffsetByPlacement" v-if="showSelector">
                    <view :class="placement == 'bottom' ? 'uni-popper__arrow_bottom' : 'uni-popper__arrow_top'"> </view>
                    <scroll-view scroll-y="true" class="uni-select__selector-scroll">
                        <view class="uni-select__selector-empty" v-if="mixinDatacomResData.length === 0">
                            <text>{{ emptyTips }}</text>
                        </view>
                        <view
                            v-else
                            class="uni-select__selector-item"
                            v-for="(item, index) in mixinDatacomResData"
                            :key="index"
                            @click="toggleItemSelection(item)">
                            <text
                                :class="{
                                    'uni-select__selector__disabled': item.disable,
                                    'uni-select__selector__selected': isSelected(item),
                                }">
                                {{ formatItemName(item) }}
                            </text>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup>
import { getRect } from "@/utils/util";

const props = defineProps({
    localdata: {
        type: Array,
        default: () => [],
    },
    value: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: [Array, String, Number],
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "请选择",
    },
    emptyTips: {
        type: String,
        default: "无选项",
    },
    clear: {
        type: Boolean,
        default: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    format: {
        type: String,
        default: "",
    },
    placement: {
        type: String,
        default: "bottom",
    },
    multiple: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["input", "update:modelValue", "change"]);

const showSelector = ref(false);
const selectedItems = ref([]);
const mixinDatacomResData = ref([]);
const windowHeight = ref(0);
const distanceTop = ref(0);
const placement = ref(props.placement);
const typePlaceholder = computed(() => props.placeholder || "请选择");

const getOffsetByPlacement = computed(() => {
    return placement.value === "top" ? `bottom: calc(100% + 12px);` : `top: calc(100% + 12px);`;
});

const bottomDistance = computed(() => {
    return windowHeight.value - distanceTop.value - curHeight.value;
});

const { proxy } = getCurrentInstance();
const toggleSelector = () => {
    if (!props.disabled) {
        showSelector.value = !showSelector.value;
    }
    nextTick(() => {
        const res = uni.$u.sys();
        windowHeight.value = res.windowHeight;
        getRect(".uni-stat__select", false, proxy).then((res) => {
            distanceTop.value = res.top;
            if (distanceTop.value > windowHeight.value - 350) {
                placement.value = "top";
            } else {
                placement.value = "bottom";
            }
        });
    });
};

const toggleItemSelection = (item) => {
    if (item.disable) return;

    if (props.multiple) {
        const index = selectedItems.value.findIndex((selected) => selected.value === item.value);
        if (index >= 0) {
            selectedItems.value.splice(index, 1);
        } else {
            selectedItems.value.push(item);
        }
    } else {
        selectedItems.value = [item];
        showSelector.value = false;
    }
    emitSelection();
};

const isSelected = (item) => {
    return selectedItems.value.some((selected) => selected.value === item.value);
};

const clearVal = () => {
    selectedItems.value = [];
    emitSelection();
};

const emitSelection = () => {
    const val = selectedItems.value.map((item) => item.value);
    if (props.multiple) {
        emit("input", val);
        emit("update:modelValue", val);
        emit("change", val);
    } else {
        emit("input", val[0]);
        emit("update:modelValue", val[0]);
        emit("change", val[0]);
    }
};

const formatItemName = (item) => {
    let { text, value, channel_code } = item;
    return channel_code ? `${text}(${channel_code})` : text || `未命名(${value})`;
};

watch(
    () => props.localdata,
    (val) => {
        if (Array.isArray(val)) {
            mixinDatacomResData.value = val;
        }
    },
    { immediate: true }
);

watch(
    () => props.modelValue,
    (val) => {
        if (props.multiple) {
            selectedItems.value = mixinDatacomResData.value.filter((item) => val?.includes(item.value));
        } else {
            selectedItems.value = mixinDatacomResData.value.filter((item) => item.value == val);
        }
    },
    { immediate: true }
);
</script>

<style lang="scss">
$uni-base-color: #6a6a6a !default;
$uni-main-color: #0065fb !default;
$uni-secondary-color: #909399 !default;
$uni-border-3: #e5e5e5;

.uni-select__selector__selected {
    font-weight: bold;
    color: $uni-main-color;
}

/* #ifndef APP-NVUE */
@media screen and (max-width: 500px) {
    .hide-on-phone {
        display: none;
    }
}

/* #endif */
.uni-stat__select {
    display: flex;
    align-items: center;
    // padding: 15px;
    /* #ifdef H5 */
    cursor: pointer;
    /* #endif */
    width: 100%;
    flex: 1;
    box-sizing: border-box;
}

.uni-stat-box {
    width: 100%;
    flex: 1;
}

.uni-stat__actived {
    width: 100%;
    flex: 1;
    // outline: 1px solid #2979ff;
}

.uni-label-text {
    font-size: 24rpx;
    font-weight: bold;
    color: $uni-base-color;
    margin: auto 0;
    margin-right: 10rpx;
}

.uni-select {
    font-size: 26rpx;
    border: 1px solid $uni-border-3;
    box-sizing: border-box;
    padding: 0 20rpx;
    padding-left: 20rpx;
    position: relative;
    /* #ifndef APP-NVUE */
    display: flex;
    user-select: none;
    /* #endif */
    flex-direction: row;
    align-items: center;
    border-bottom: solid 1px $uni-border-3;
    width: 100%;
    flex: 1;
    @apply rounded-lg;
    &--disabled {
        background-color: #f5f7fa;
        cursor: not-allowed;
    }
}

.uni-select__label {
    font-size: 28rpx;
    // line-height: 22px;
    min-height: 82rpx;
    padding-right: 10rpx;
    color: $uni-secondary-color;
}

.uni-select__input-box {
    min-height: 82rpx;
    position: relative;
    /* #ifndef APP-NVUE */
    display: flex;
    /* #endif */
    flex: 1;
    flex-direction: row;
    align-items: center;
}

.uni-select__input {
    flex: 1;
    font-size: 28rpx;
    height: 44rpx;
    line-height: 44rpx;
}

.uni-select__input-plac {
    font-size: 28rpx;
    color: $uni-secondary-color;
}

.uni-select__selector {
    /* #ifndef APP-NVUE */
    box-sizing: border-box;
    /* #endif */
    position: absolute;
    left: 0;
    width: 100%;
    background-color: #ffffff;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    z-index: 3000;
    padding: 4px 0;
}

.uni-select__selector-scroll {
    /* #ifndef APP-NVUE */
    max-height: 400rpx;
    box-sizing: border-box;
    /* #endif */
}

/* #ifdef H5 */
@media (min-width: 768px) {
    .uni-select__selector-scroll {
        max-height: 600px;
    }
}

/* #endif */

.uni-select__selector-empty,
.uni-select__selector-item {
    /* #ifndef APP-NVUE */
    display: flex;
    cursor: pointer;
    /* #endif */
    line-height: 35px;
    font-size: 14px;
    text-align: center;
    /* border-bottom: solid 1px $uni-border-3; */
    padding: 0px 10px;
}

.uni-select__selector-item:hover {
    background-color: #f9f9f9;
}

.uni-select__selector-empty:last-child,
.uni-select__selector-item:last-child {
    /* #ifndef APP-NVUE */
    border-bottom: none;
    /* #endif */
}

.uni-select__selector__disabled {
    opacity: 0.4;
    cursor: default;
}

/* picker 弹出层通用的指示小三角 */
.uni-popper__arrow_bottom,
.uni-popper__arrow_bottom::after,
.uni-popper__arrow_top,
.uni-popper__arrow_top::after {
    position: absolute;
    display: block;
    width: 0;
    height: 0;
    border-color: transparent;
    border-style: solid;
    border-width: 6px;
}

.uni-popper__arrow_bottom {
    filter: drop-shadow(0 2px 12px rgba(0, 0, 0, 0.03));
    top: -6px;
    left: 10%;
    margin-right: 3px;
    border-top-width: 0;
    border-bottom-color: #ebeef5;
}

.uni-popper__arrow_bottom::after {
    content: " ";
    top: 1px;
    margin-left: -6px;
    border-top-width: 0;
    border-bottom-color: #fff;
}

.uni-popper__arrow_top {
    filter: drop-shadow(0 2px 12px rgba(0, 0, 0, 0.03));
    bottom: -6px;
    left: 10%;
    margin-right: 3px;
    border-bottom-width: 0;
    border-top-color: #ebeef5;
}

.uni-popper__arrow_top::after {
    content: " ";
    bottom: 1px;
    margin-left: -6px;
    border-bottom-width: 0;
    border-top-color: #fff;
}

.uni-select__input-text {
    // width: 280px;
    width: 100%;
    color: $uni-main-color;
    white-space: nowrap;
    text-overflow: ellipsis;
    -o-text-overflow: ellipsis;
    overflow: hidden;
}

.uni-select__input-placeholder {
    color: $uni-base-color;
    font-size: 26rpx;
}

.uni-select--mask {
    position: fixed;
    top: 0;
    bottom: 0;
    right: 0;
    left: 0;
    z-index: 2;
}
</style>
