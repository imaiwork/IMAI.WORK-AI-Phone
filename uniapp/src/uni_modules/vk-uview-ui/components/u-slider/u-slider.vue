<template>
    <!-- 触摸挂在整条轨道上：最大值时滑钮半溢出，只点滑钮会失效；整轨拖动可覆盖音量/语速两端 -->
    <view
        class="u-slider"
        @tap="onClick"
        @touchstart.stop="onTouchStart"
        @touchmove.stop.prevent="onTouchMove"
        @touchend.stop="onTouchEnd"
        @touchcancel.stop="onTouchEnd"
        :class="[disabled ? 'u-slider--disabled' : '']"
        :style="{
            backgroundColor: inactiveColor,
        }">
        <view
            class="u-slider__gap"
            :style="[
                barStyle,
                {
                    height: height + 'rpx',
                    backgroundColor: activeColor,
                },
            ]">
            <view class="u-slider__button-wrap">
                <slot v-if="$slots.default || $slots.$default" />
                <view
                    v-else
                    class="u-slider__button"
                    :style="[
                        blockStyle,
                        {
                            height: blockWidth + 'rpx',
                            width: blockWidth + 'rpx',
                            backgroundColor: blockColor,
                        },
                    ]"></view>
            </view>
        </view>
    </view>
</template>

<script>
/**
 * slider 滑块选择器
 * @tutorial https://uviewui.com/components/slider.html
 * @property {Number | String} value 滑块默认值（默认0）
 * @property {Number | String} min 最小值（默认0）
 * @property {Number | String} max 最大值（默认100）
 * @property {Number | String} step 步长（默认1）
 * @property {Number | String} blockWidth 滑块宽度，高等于宽（30）
 * @property {Number | String} height 滑块条高度，单位rpx（默认6）
 * @property {String} inactiveColor 底部条背景颜色（默认#c0c4cc）
 * @property {String} activeColor 底部选择部分的背景颜色（默认#2979ff）
 * @property {String} blockColor 滑块颜色（默认#ffffff）
 * @property {Object} blockStyle 给滑块自定义样式，对象形式
 * @property {Boolean} disabled 是否禁用滑块(默认为false)
 * @event {Function} start 滑动触发
 * @event {Function} moving 正在滑动中
 * @event {Function} end 滑动结束
 * @example <u-slider v-model="value" />
 */
export default {
    name: "u-slider",
    emits: ["update:modelValue", "input", "start", "moving", "end"],
    props: {
        // 当前进度百分比值，范围0-100
        value: {
            type: [Number, String],
            default: 0,
        },
        modelValue: {
            type: [Number, String],
            default: 0,
        },
        // 是否禁用滑块
        disabled: {
            type: Boolean,
            default: false,
        },
        // 滑块宽度，高等于宽，单位rpx
        blockWidth: {
            type: [Number, String],
            default: 30,
        },
        // 最小值
        min: {
            type: [Number, String],
            default: 0,
        },
        // 最大值
        max: {
            type: [Number, String],
            default: 100,
        },
        // 步进值
        step: {
            type: [Number, String],
            default: 1,
        },
        // 滑块条高度，单位rpx
        height: {
            type: [Number, String],
            default: 6,
        },
        // 进度条的激活部分颜色
        activeColor: {
            type: String,
            default: "#2979ff",
        },
        // 进度条的背景颜色
        inactiveColor: {
            type: String,
            default: "#c0c4cc",
        },
        // 滑块的背景颜色
        blockColor: {
            type: String,
            default: "#ffffff",
        },
        // 用户对滑块的自定义颜色
        blockStyle: {
            type: Object,
            default() {
                return {};
            },
        },
    },
    data() {
        return {
            startX: 0,
            status: "end",
            newValue: 0,
            distanceX: 0,
            startValue: 0,
            // 拖动结束后忽略紧随的 tap，避免与 touch 重复改值
            skipClick: false,
            barStyle: {},
            sliderRect: {
                left: 0,
                width: 0,
            },
        };
    },
    watch: {
        valueCom(n) {
            // 只有在非滑动状态时，才可以通过value更新滑块值，这里监听，是为了让用户触发
            if (this.status == "end") this.updateValue(this.valueCom, false);
        },
    },
    created() {
        this.updateValue(this.valueCom, false);
    },
    mounted() {
        // 弹层内首次 mounted 时宽度常为 0，延迟再测一次
        this.$nextTick(() => {
            this.refreshSliderRect();
            setTimeout(() => {
                this.refreshSliderRect();
            }, 100);
        });
    },
    computed: {
        valueCom() {
            // #ifndef VUE3
            return this.value;
            // #endif

            // #ifdef VUE3
            return this.modelValue;
            // #endif
        },
    },
    methods: {
        refreshSliderRect() {
            return this.$uGetRect(".u-slider").then((rect) => {
                if (rect && rect.width) {
                    this.sliderRect = rect;
                }
                return rect;
            });
        },
        onTouchStart(event) {
            if (this.disabled) return;
            // 每次拖动前刷新尺寸，避免弹窗内首次测量为 0 导致卡在最大值
            this.refreshSliderRect();
            this.startX = 0;
            // 触摸点集
            let touches = event.touches[0];
            // 触摸点到屏幕左边的距离
            this.startX = touches.clientX;
            // 此处的this.value虽为props值，但是通过$emit('input')进行了修改
            this.startValue = this.format(this.valueCom);
            // 标示当前的状态为开始触摸滑动
            this.status = "start";
        },
        onTouchMove(event) {
            if (this.disabled) return;
            // 宽度未就绪时不要参与计算，否则 distanceX/0 → Infinity → 被钳成 max，表现为滑到最大后无法回调
            if (!this.sliderRect.width) {
                this.refreshSliderRect();
                return;
            }
            // 连续触摸的过程会一直触发本方法，但只有手指触发且移动了才被认为是拖动了，才发出事件
            // 触摸后第一次移动已经将status设置为moving状态，故触摸第二次移动不会触发本事件
            if (this.status == "start") this.$emit("start");
            let touches = event.touches[0];
            // 滑块的左边不一定跟屏幕左边接壤，所以需要减去最外层父元素的左边值
            this.distanceX = touches.clientX - this.sliderRect.left;
            // 获得移动距离对整个滑块的百分比值，此为带有多位小数的值，不能用此更新视图
            // 否则造成通信阻塞，需要每改变一个step值时修改一次视图
            const rawValue =
                (this.distanceX / this.sliderRect.width) * (Number(this.max) - Number(this.min)) + Number(this.min);
            this.newValue = this.format(rawValue);
            this.status = "moving";
            // 发出moving事件
            this.$emit("moving");
            this.updateValue(this.newValue, true);
        },
        onTouchEnd() {
            if (this.disabled) return;
            if (this.status === "moving") {
                this.updateValue(this.newValue, false);
                this.$emit("end");
                // 阻止 touch 结束后的 tap 再写一次值
                this.skipClick = true;
                setTimeout(() => {
                    this.skipClick = false;
                }, 50);
            }
            this.status = "end";
        },
        updateValue(value, drag) {
            // 去掉小数部分，同时也是对step步进的处理
            const width = this.format(value);
            const min = Number(this.min);
            const max = Number(this.max);

            // 设置移动的百分比值
            const percent = max === min ? 0 : ((width - min) / (max - min)) * 100;

            let barStyle = {
                width: percent + "%",
            };
            // 移动期间无需过渡动画
            if (drag == true) {
                barStyle.transition = "none";
            } else {
                // 非移动期间，删掉对过渡为空的声明，让css中的声明起效
                delete barStyle.transition;
            }
            // 修改value值
            this.$emit("input", width);
            this.$emit("update:modelValue", width);
            this.barStyle = barStyle;
        },
        format(value) {
            // 将小数变成整数，为了减少对视图的更新，造成视图层与逻辑层的阻塞
            const step = Number(this.step);
            const min = Number(this.min);
            const max = Number(this.max);

            let formattedValue = Math.round(Math.max(min, Math.min(value, max)) / step) * step;

            const stepString = String(this.step);
            if (stepString.indexOf(".") !== -1) {
                const precision = stepString.length - stepString.indexOf(".") - 1;
                formattedValue = Number(formattedValue.toFixed(precision));
            }
            return formattedValue;
        },
        onClick(event) {
            if (this.disabled || this.skipClick) return;
            // 直接点击滑块的情况，计算方式与onTouchMove方法相同
            if (!this.sliderRect.width) {
                this.refreshSliderRect().then(() => {
                    if (!this.sliderRect.width) return;
                    this.applyClickValue(event);
                });
                return;
            }
            this.applyClickValue(event);
        },
        applyClickValue(event) {
            const value =
                ((event.detail.x - this.sliderRect.left) / this.sliderRect.width) *
                    (Number(this.max) - Number(this.min)) +
                Number(this.min);
            this.updateValue(value, false);
        },
    },
};
</script>

<style lang="scss" scoped>
@import "../../libs/css/style.components.scss";

.u-slider {
    position: relative;
    border-radius: 999px;
    border-radius: 999px;
    background-color: #ebedf0;
    /* 加大纵向热区，最大值时不必精准点中半溢出的滑钮 */
    padding: 20rpx 0;
    background-clip: content-box;
}

.u-slider:before {
    position: absolute;
    right: 0;
    left: 0;
    content: "";
    top: 0;
    bottom: 0;
}

.u-slider__gap {
    position: relative;
    border-radius: inherit;
    transition: width 0.2s;
    transition: width 0.2s;
    background-color: #1989fa;
}

.u-slider__button {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    background-color: #fff;
    cursor: pointer;
}

.u-slider__button-wrap {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translate3d(50%, -50%, 0);
}

.u-slider--disabled {
    opacity: 0.5;
}
</style>
