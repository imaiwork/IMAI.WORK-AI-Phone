<template>
    <view
        id="drag-button"
        class="drag-container"
        :class="{ transition: autoDocking && !moving }"
        :style="{
            left: `${left}px`,
            top: `${top}px`,
            width: `${size}rpx`,
            height: `${size}rpx`,
            zIndex: zIndex
        }"
        @touchend="touchend"
        @touchmove.stop.prevent="touchmove"
    >
        <slot />
    </view>
</template>

<script>
export default {
    name: 'DragButton',
    props: {
        /**
         * 按钮大小
         */
        size: {
            type: Number,
            default: 200
        },
        /**
         * 层级
         */
        zIndex: {
            type: Number,
            default: 999
        },
        /**
         * x轴边界限制
         */
        xEdge: {
            type: Number,
            default: 0
        },
        /**
         * y轴边界限制
         */
        yEdge: {
            type: Number,
            default: 50
        },
        /**
         * 自动停靠
         */
        autoDocking: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            top: 500,
            left: 300,
            width: 0,
            height: 0,
            moving: true
        }
    },
    mounted() {
        this.init()
    },
    methods: {
        init() {
            // 获取窗口尺寸
            const { windowWidth, windowHeight, windowTop } =
                uni.getSystemInfoSync()
            this._windowWidth = windowWidth
            this._windowHeight = windowHeight
            if (windowTop) {
                this._windowHeight += windowTop
            }
            // 计算按钮初始位置
            const query = uni.createSelectorQuery().in(this)
            query
                .select('#drag-button')
                .boundingClientRect((data) => {
                    if (!data) return
                    const { width, height } = data
                    this.width = width
                    this.height = height
                    this._offsetWidth = width / 2
                    this._offsetHeight = height / 2
                    this.left = this._windowWidth - this.width - this.xEdge
                    this.top = this._windowHeight - this.height - this.yEdge
                })
                .exec()
        },

        // 拖动
        touchmove({ touches }) {
            if (touches.length !== 1) return false

            this.moving = true
            const { clientX, clientY } = touches[0]
            this.left = clientX - this._offsetWidth

            const _clientY = clientY - this._offsetHeight

            this.top = _clientY
        },

        // 松手
        touchend() {
            // 左右边界，松手自动停靠
            if (this.autoDocking) {
                const rigthEdge = this._windowWidth - this.width - this.xEdge
                if (this.left < this._windowWidth / 2 - this._offsetWidth) {
                    this.left = this.xEdge
                } else {
                    this.left = rigthEdge
                }
            }

            // 上下边界
            const bottomEdge = this._windowHeight - this.height - this.yEdge
            if (this.top < 50) {
                this.top = 50
            } else if (this.top > bottomEdge) {
                this.top = bottomEdge
            }

            this.moving = false
        }
    }
}
</script>

<style lang="scss" scoped>
.drag-container {
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    position: fixed;

    &.transition {
        transition: all 0.3s ease;
    }
}
</style>
