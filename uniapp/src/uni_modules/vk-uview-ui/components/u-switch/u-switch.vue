<template>
    <view
        class="u-switch"
        :class="[valueCom ? 'u-switch--on' : '', disabled ? 'u-switch--disabled' : '']"
        @tap="onClick"
        :style="[switchStyle]">
        <view
            class="u-switch__node node-class"
            :style="{
                width: $u.addUnit(size),
                height: $u.addUnit(size),
            }">
            <u-loading :show="loading" class="u-switch__loading" :size="size * 0.6" :color="loadingColor" />
        </view>
    </view>
</template>

<script>
export default {
    name: "u-switch",
    emits: ["update:modelValue", "input", "change"],
    props: {
        value: {
            type: [Boolean, Number, String],
            default: false,
        },
        modelValue: {
            type: [Boolean, Number, String],
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        size: {
            type: [Number, String],
            default: 50,
        },
        activeColor: {
            type: String,
            default: "#2979ff",
        },
        inactiveColor: {
            type: String,
            default: "#ffffff",
        },
        vibrateShort: {
            type: Boolean,
            default: false,
        },
        activeValue: {
            type: [Number, String, Boolean],
            default: true,
        },
        inactiveValue: {
            type: [Number, String, Boolean],
            default: false,
        },
    },
    computed: {
        rawValue() {
            // #ifndef VUE3
            return this.value;
            // #endif
            // #ifdef VUE3
            return this.modelValue;
            // #endif
        },
        valueCom() {
            return String(this.rawValue) === String(this.activeValue);
        },
        switchStyle() {
            let style = {};
            style.fontSize = this.size + "rpx";
            style.backgroundColor = this.valueCom ? this.activeColor : this.inactiveColor;
            return style;
        },
        loadingColor() {
            return this.valueCom ? this.activeColor : null;
        },
    },
    methods: {
        onClick() {
            if (!this.disabled && !this.loading) {
                if (this.vibrateShort) uni.vibrateShort();
                const newVal = this.valueCom ? this.inactiveValue : this.activeValue;
                // #ifndef VUE3
                this.$emit("input", newVal);
                // #endif
                // #ifdef VUE3
                this.$emit("update:modelValue", newVal);
                // #endif
                this.$nextTick(() => {
                    this.$emit("change", newVal);
                });
            }
        },
    },
};
</script>

<style lang="scss" scoped>
@import "../../libs/css/style.components.scss";

.u-switch {
    position: relative;
    /* #ifndef APP-NVUE */
    display: inline-block;
    /* #endif */
    box-sizing: initial;
    width: 2em;
    height: 1em;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 1em;
    transition: background-color 0.3s;
    font-size: 50rpx;
}

.u-switch__node {
    @include vue-flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 0;
    left: 0;
    border-radius: 100%;
    z-index: 1;
    background-color: #fff;
    box-shadow: 0 3px 1px 0 rgba(0, 0, 0, 0.05), 0 2px 2px 0 rgba(0, 0, 0, 0.1), 0 3px 3px 0 rgba(0, 0, 0, 0.05);
    transition: transform 0.3s cubic-bezier(0.3, 1.05, 0.4, 1.05),
        -webkit-transform 0.3s cubic-bezier(0.3, 1.05, 0.4, 1.05);
}

.u-switch__loading {
    @include vue-flex;
    align-items: center;
    justify-content: center;
}

.u-switch--on {
    background-color: #1989fa;
}

.u-switch--on .u-switch__node {
    transform: translateX(100%);
}

.u-switch--disabled {
    opacity: 0.4;
}
</style>
