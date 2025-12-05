import { ref } from "vue";

interface Options {
    key?: string;
    time?: number;
    totalTime?: number;
    count?: number;
    callback?(): void;
}
let pollingDict: any = {};

export default function usePolling(fun: any, options: Options = {}) {
    const { key, time = 2000, totalTime, count, callback = () => false } = options;

    let timer: any = null;
    let endTime: any = null;
    let totalCount = 0;
    let stopped = false; // 添加一个stopped标志

    const result = ref(null);
    const error = ref(null);

    function run() {
        if (stopped) return; // 如果stopped为true，则不再执行轮询
        if (endTime && endTime <= Date.now()) {
            end();
            callback();
            return;
        }
        if (count && totalCount >= count) {
            end();
            callback();
            return;
        }
        totalCount++;
        timer = setTimeout(() => {
            fun()
                .then((res: any) => {
                    result.value = res;
                    run();
                })
                .catch((err: any) => {
                    error.value = err;
                });
        }, time);
    }

    const start = () => {
        stopped = false; // 重置stopped标志
        end(); // add this line
        if (key && pollingDict[key]) {
            pollingDict[key].end();
            delete pollingDict[key];
        }
        endTime = totalTime ? Date.now() + totalTime : null;
        run();
        if (key) {
            pollingDict[key] = { end };
        }
    };

    const end = () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
            endTime = null;
            totalCount = 0;
            stopped = true; // 设置stopped标志为true
            if (key) delete pollingDict[key];
        }
    };

    return {
        start,
        end,
        error,
        result,
    };
}
