/**
 * 校验时间段列表
 * @param {Array<[string, string]>} list 格式如 [['09:00', '09:30'], ['10:00', '10:30']]
 * @returns {{valid:boolean, errorIndexes:number[]}}
 */
export function validateSchedule(list: any[]) {
    const toMin = (t: any) => {
        if (typeof t !== "string" || t === "") return NaN;
        const parts = t.split(":");
        if (parts.length !== 2) return NaN;
        const [h, m] = parts.map(Number);
        if (isNaN(h) || isNaN(m) || h < 0 || h > 23 || m < 0 || m > 59) return NaN;
        return h * 60 + m;
    };

    const schedule = list.map((item) => ({
        start_time: item?.[0],
        end_time: item?.[1],
        s: toMin(item?.[0]),
        e: toMin(item?.[1]),
    }));

    const errorSet = new Set<number>(); // Use a Set to store unique error indices

    schedule.forEach((cur, i, arr) => {
        if (cur.start_time == null || cur.start_time === "") {
            errorSet.add(i);
        }
        if (cur.end_time == null || cur.end_time === "") {
            errorSet.add(i);
        }

        if (isNaN(cur.s) || isNaN(cur.e)) {
            errorSet.add(i);
            return; // Skip further checks for this invalid item
        }

        if (cur.s >= cur.e) {
            errorSet.add(i);
        }

        if (i > 0) {
            const prev = arr[i - 1];
            if (!isNaN(prev.e) && cur.s < prev.e) {
                errorSet.add(i - 1);
                errorSet.add(i);
            }
        }
    });

    const errorIndexes = Array.from(errorSet).sort((a, b) => a - b); // Convert Set to sorted Array
    const isValid = errorIndexes.length === 0;
    return { valid: isValid, errorIndexes };
}
/**
 * 检查相邻 publish_time 间隔是否 ≥ minGapMinutes
 * @param {Array<{publish_time:string}>} arr
 * @param {number} minGapMinutes  允许的最小间隔（分钟）
 * @returns {{valid:boolean, errorIndexes?:number[], gapMinutes?:number}}
 */
export function checkMinGap(arr, minGapMinutes = 15) {
    if (!Number.isFinite(minGapMinutes) || minGapMinutes <= 0) {
        throw new TypeError("minGapMinutes 必须是正数");
    }

    // 生成带原始索引的新数组，方便最后取回真正的下标
    const indexed = arr.map((item, idx) => ({ ...item, _idx: idx }));
    // 按时间升序
    indexed.sort((a, b) => new Date(a.publish_time).getTime() - new Date(b.publish_time).getTime());

    for (let i = 1; i < indexed.length; i++) {
        const prev = indexed[i - 1];
        const curr = indexed[i];
        const gap = (new Date(curr.publish_time).getTime() - new Date(prev.publish_time).getTime()) / (1000 * 60);

        if (gap < minGapMinutes) {
            return {
                valid: false,
                errorIndexes: [prev._idx, curr._idx], // 两条错误项的原始索引
                gapMinutes: gap,
            };
        }
    }
    return { valid: true };
}
