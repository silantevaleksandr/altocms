{if E::IsAdmin()}
<div class="b-page-stats">
    {hook run='statistics_performance_begin'}
    <table>
        <tr>
            <td>
                <h4 class="b-page-stats-head">Database</h4>
                query: <strong>{$aStatsPerformance.sql.count}</strong><br/>
                time: <strong>{$aStatsPerformance.sql.time}</strong>
            </td>
            <td>
                <h4 class="b-page-stats-head">Cache ({$aStatsPerformance.cache.mode})</h4>
                query: <strong>{$aStatsPerformance.cache.count}</strong><br/>
                &mdash; set: <strong>{$aStatsPerformance.cache.count_set}</strong><br/>
                &mdash; get: <strong>{$aStatsPerformance.cache.count_get}</strong><br/>
                time: <strong>{$aStatsPerformance.cache.time}</strong>
            </td>
            <td>
                <h4 class="b-page-stats-head">Viewer</h4>
                total time: <strong>{$aStatsPerformance.viewer.total}</strong><br/>
                &mdash; render calls: <strong>{$aStatsPerformance.viewer.count}</strong><br/>
                &mdash; render time: <strong>{$aStatsPerformance.viewer.time}</strong><br/>
            </td>
            <td>
                <h4 class="b-page-stats-head">PHP</h4>
                time load modules: <strong>{$aStatsPerformance.engine.time_load_module}</strong><br/>
                included files: <br/>
                &mdash; count: <strong>{$aStatsPerformance.engine.files_count}</strong><br/>
                &mdash; time: <strong>{$aStatsPerformance.engine.files_time}</strong><br/>
                full time: <strong>{$aStatsPerformance.engine.full_time}</strong>
            </td>
            <td>
                <h4 class="b-page-stats-head">Memory</h4>
                memory limit: <strong>{$aStatsPerformance.memory.limit}</strong><br/>
                memory usage: <strong>{$aStatsPerformance.memory.usage}</strong><br/>
                peak usage: <strong>{$aStatsPerformance.memory.peak}</strong>
            </td>
            {hook run='statistics_performance_item'}
        </tr>
    </table>
    {hook run='statistics_performance_end'}
</div>
{/if}