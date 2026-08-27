import { ref } from 'vue'

interface Options {
    key?: string
    time?: number
    totalTime?: number
    count?: number
    callback?(): void
}
const pollingDict: any = {}

export default function usePolling(fun: any, options: Options = {}) {
    const { key, time = 2000, totalTime, count, callback = () => false } = options

    let timer: any = null
    let endTime: any = null
    let totalCount = 0
    let stopped = false // 添加一个stopped标志
    // 轮询代际：fun() 在途时调用 start() 会让旧链的 .then 回调再次 run()，
    // 与新链并行出双倍轮询且旧链无法被 end() 停掉；每次 start/end 递增代际，旧链回调直接失效
    let generation = 0

    const result = ref(null)
    const error = ref(null)

    function run(gen: number) {
        if (stopped || gen !== generation) return // stopped 或代际过期都不再执行轮询
        if (endTime && endTime <= Date.now()) {
            end()
            callback()
            return
        }
        if (count && totalCount >= count) {
            end()
            callback()
            return
        }
        totalCount++
        timer = setTimeout(() => {
            fun()
                .then((res: any) => {
                    result.value = res
                    run(gen)
                })
                .catch((err: any) => {
                    error.value = err
                })
        }, time)
    }

    const start = () => {
        end() // 先收口旧轮询（end 会把 stopped 置 true）
        stopped = false // 再重置stopped标志，保证 run() 能启动
        generation++
        if (key && pollingDict[key]) {
            pollingDict[key].end()
            delete pollingDict[key]
        }
        endTime = totalTime ? Date.now() + totalTime : null
        run(generation)
        if (key) {
            pollingDict[key] = { end }
        }
    }

    const end = () => {
        generation++ // 让在途 fun() 的 .then 回调失效，避免 end 后旧链继续调度
        if (timer) {
            clearTimeout(timer)
            timer = null
            endTime = null
            totalCount = 0
            stopped = true // 设置stopped标志为true
            if (key) delete pollingDict[key]
        }
    }

    return {
        start,
        end,
        error,
        result
    }
}
