<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import * as d3 from 'd3';

interface DietPeriod {
    id: number;
    name: string;
    phase_type: string;
    start_date: string;
    end_date: string;
    target_calories?: number;
    target_protein?: number;
}

interface Props {
    nutritionStats: {
        totalCalories: number;
        totalProtein: number;
        totalCarbs: number;
        totalFat: number;
        caloriesByDay: Array<{ date: string; calories: number; notes: string[] }>;
        macrosByDay: Array<{ date: string; protein: number; carbs: number; fat: number }>;
        averageDailyCalories: number;
        excludedFoodData: Record<string, string | null>;
    };
    weightStats: {
        weightByDay: Array<{ date: string; weight: number; notes?: string }>;
        excludedWeightData: Record<string, string | null>;
    };
    currentMonth: string;
    dietPeriods: DietPeriod[];
    selectedDietPeriodId?: number | null;
    dateRange: {
        start: string;
        end: string;
    };
}

const props = defineProps<Props>();

const calorieChart = ref<HTMLDivElement>();
const weightChart = ref<HTMLDivElement>();
const proteinChart = ref<HTMLDivElement>();
const carbsChart = ref<HTMLDivElement>();
const fatChart = ref<HTMLDivElement>();

const showMacroCharts = ref({
    protein: true,
    carbs: true,
    fat: true
});

const monthOptions = computed(() => {
    const months = [];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        months.push({
            value: date.toISOString().slice(0, 7),
            label: date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
        });
    }
    return months;
});

function changeMonth(month: string) {
    router.get('/dashboard', { month }, { preserveState: true });
}

function changeDietPeriod(dietPeriodId: string) {
    if (dietPeriodId === '') {
        // Show current month when no diet period selected
        router.get('/dashboard', { month: props.currentMonth }, { preserveState: true });
    } else {
        router.get('/dashboard', { diet_period_id: dietPeriodId }, { preserveState: true });
    }
}

const currentViewType = computed(() => {
    return props.selectedDietPeriodId ? 'diet-period' : 'month';
});

const currentViewLabel = computed(() => {
    if (props.selectedDietPeriodId) {
        const selectedPeriod = props.dietPeriods.find(p => p.id === props.selectedDietPeriodId);
        return selectedPeriod ? selectedPeriod.name : 'Unknown Period';
    } else {
        const month = monthOptions.value.find(m => m.value === props.currentMonth);
        return month ? month.label : 'Current Month';
    }
});

onMounted(() => {
    if (calorieChart.value && props.nutritionStats.caloriesByDay.length > 0) {
        createCalorieChart();
    }
    if (weightChart.value && props.weightStats.weightByDay.length > 0) {
        createWeightChart();
    }
    // Create macro charts by default
    if (props.nutritionStats.macrosByDay.length > 0) {
        nextTick(() => {
            createMacroChart('protein');
            createMacroChart('carbs');
            createMacroChart('fat');
        });
    }
});

// Watch for changes to recreate charts when month changes
watch(
    () => props.nutritionStats.caloriesByDay,
    () => {
        if (calorieChart.value) {
            if (props.nutritionStats.caloriesByDay.length > 0) {
                createCalorieChart();
            } else {
                // Clear chart when no data
                d3.select(calorieChart.value).selectAll('*').remove();
            }
        }
    }
);

watch(
    () => props.weightStats.weightByDay,
    () => {
        if (weightChart.value) {
            if (props.weightStats.weightByDay.length > 0) {
                createWeightChart();
            } else {
                // Clear chart when no data
                d3.select(weightChart.value).selectAll('*').remove();
            }
        }
    }
);

function calculateLinearRegression(data: number[][]): { slope: number; intercept: number } {
    const n = data.length;
    if (n < 2) return { slope: 0, intercept: 0 };
    
    const sumX = data.reduce((sum, point) => sum + point[0], 0);
    const sumY = data.reduce((sum, point) => sum + point[1], 0);
    const sumXY = data.reduce((sum, point) => sum + point[0] * point[1], 0);
    const sumXX = data.reduce((sum, point) => sum + point[0] * point[0], 0);
    
    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;
    
    return { slope, intercept };
}

function createCalorieChart() {
    if (!calorieChart.value) return;
    
    const margin = { top: 60, right: 30, bottom: 40, left: 60 };
    const containerWidth = Math.min(calorieChart.value.clientWidth, calorieChart.value.parentElement?.clientWidth || 800);
    const width = containerWidth - margin.left - margin.right;
    const height = 350 - margin.top - margin.bottom;

    // Clear previous chart and tooltips
    d3.select(calorieChart.value).selectAll('*').remove();
    d3.selectAll('.tooltip').remove();

    const svg = d3.select(calorieChart.value)
        .append('svg')
        .attr('width', '100%')
        .attr('height', height + margin.top + margin.bottom)
        .attr('viewBox', `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    const data = props.nutritionStats.caloriesByDay;
    
    const parseDate = d3.timeParse('%Y-%m-%d');
    const formatDate = d3.timeFormat('%m/%d');
    
    const processedData = data.map(d => ({
        date: parseDate(d.date)!,
        calories: d.calories,
        notes: d.notes || []
    })).filter(d => d.date !== null);

    const x = d3.scaleTime()
        .domain(d3.extent(processedData, d => d.date) as [Date, Date])
        .range([0, width]);

    const maxCalories = d3.max(processedData, d => d.calories) || 0;
    const minCalories = d3.min(processedData, d => d.calories) || 0;
    
    // Add 30% buffer to top and bottom, rounded to nearest whole number
    const buffer = (maxCalories - minCalories) * 0.3;
    const yMin = Math.max(0, Math.round(minCalories - buffer));
    const yMax = Math.round(maxCalories + buffer);
    
    const y = d3.scaleLinear()
        .domain([yMin, yMax])
        .range([height, 0]);

    // Determine if we're in dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#F3F4F6' : '#374151';
    const gridColor = isDarkMode ? '#6B7280' : '#E5E7EB';
    const axisColor = isDarkMode ? '#9CA3AF' : '#9CA3AF';

    // Add diet period backgrounds
    const phaseColors = {
        cut: isDarkMode ? 'rgba(239, 68, 68, 0.1)' : 'rgba(239, 68, 68, 0.05)',
        bulk: isDarkMode ? 'rgba(34, 197, 94, 0.1)' : 'rgba(34, 197, 94, 0.05)', 
        maintenance: isDarkMode ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)'
    };

    props.dietPeriods.forEach(period => {
        const startX = x(parseDate(period.start_date)!);
        const endX = x(parseDate(period.end_date)!);
        const color = phaseColors[period.phase_type as keyof typeof phaseColors];
        
        // Add background rectangle for period
        svg.append('rect')
            .attr('x', startX)
            .attr('y', 0)
            .attr('width', endX - startX)
            .attr('height', height)
            .attr('fill', color)
            .attr('opacity', 0.3);
            
        // Add period label at the top
        if (endX - startX > 80) { // Only show label if there's enough space
            svg.append('text')
                .attr('x', startX + (endX - startX) / 2)
                .attr('y', -10)
                .style('text-anchor', 'middle')
                .style('fill', textColor)
                .style('font-size', '12px')
                .style('font-weight', '500')
                .text(`${period.name} (${period.phase_type})`);
        }

        // Add target calorie line if available
        if (period.target_calories) {
            svg.append('line')
                .attr('x1', startX)
                .attr('x2', endX)
                .attr('y1', y(period.target_calories))
                .attr('y2', y(period.target_calories))
                .attr('stroke', isDarkMode ? '#F59E0B' : '#D97706')
                .attr('stroke-width', 2)
                .attr('stroke-dasharray', '5,5')
                .attr('opacity', 0.8);
                
            // Add target label
            svg.append('text')
                .attr('x', endX - 5)
                .attr('y', y(period.target_calories) - 5)
                .style('text-anchor', 'end')
                .style('fill', isDarkMode ? '#F59E0B' : '#D97706')
                .style('font-size', '11px')
                .style('font-weight', '500')
                .text(`Target: ${Math.round(period.target_calories)}`);
        }
    });

    // Create line generator
    const line = d3.line<any>()
        .x(d => x(d.date))
        .y(d => y(d.calories))
        .curve(d3.curveMonotoneX);

    // Calculate linear regression
    const regression = calculateLinearRegression(processedData.map(d => [d.date.getTime(), d.calories]));
    const regressionLine = processedData.map(d => ({
        date: d.date,
        value: regression.slope * d.date.getTime() + regression.intercept
    }));

    // Add regression line first (so it appears behind the main line)
    svg.append('path')
        .datum(regressionLine)
        .attr('fill', 'none')
        .attr('stroke', '#10B981')
        .attr('stroke-width', 1)
        .attr('stroke-dasharray', '5,5')
        .attr('opacity', 0.7)
        .attr('d', d3.line<any>()
            .x(d => x(d.date))
            .y(d => y(d.value))
        );

    // Add the main line
    svg.append('path')
        .datum(processedData)
        .attr('fill', 'none')
        .attr('stroke', '#10B981')
        .attr('stroke-width', 2)
        .attr('d', line);

    // Create tooltip div
    const tooltip = d3.select('body')
        .append('div')
        .attr('class', 'tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('background', isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)')
        .style('border', `1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}`)
        .style('border-radius', '8px')
        .style('padding', '12px')
        .style('font-size', '14px')
        .style('box-shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.1)')
        .style('backdrop-filter', 'blur(8px)')
        .style('z-index', '1000')
        .style('pointer-events', 'none');

    // Add exclusion bars for food data first (so dots render on top)
    const parseExclusionDate = d3.timeParse('%Y-%m-%d');
    Object.entries(props.nutritionStats.excludedFoodData).forEach(([dateStr, note]) => {
        const exclusionDate = parseExclusionDate(dateStr);
        if (exclusionDate && x.domain()[0] <= exclusionDate && exclusionDate <= x.domain()[1]) {
            svg.append('rect')
                .attr('class', 'exclusion-bar')
                .attr('x', x(exclusionDate) - 2.5) // Center the 5px bar
                .attr('y', 0)
                .attr('width', 5)
                .attr('height', height)
                .attr('fill', 'rgba(239, 68, 68, 0.6)') // Semi-transparent red
                .style('cursor', 'pointer')
                .on('mouseover', function(event) {
                    const formatTooltipDate = d3.timeFormat('%B %d, %Y');
                    const tooltipContent = `
                        <div style="color: ${textColor};">
                            <div style="font-weight: 600; margin-bottom: 6px; color: #DC2626;">Excluded: ${formatTooltipDate(exclusionDate)}</div>
                            <div style="margin-bottom: 4px;">Food data excluded from calculations</div>
                            ${note ? `<div style="border-top: 1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}; padding-top: 6px; margin-top: 6px; font-style: italic;">${note}</div>` : ''}
                        </div>
                    `;
                    
                    tooltip.transition().duration(200).style('opacity', 1);
                    tooltip.html(tooltipContent)
                        .style('left', (event.pageX + 10) + 'px')
                        .style('top', (event.pageY - 10) + 'px');
                })
                .on('mousemove', function(event) {
                    tooltip.style('left', (event.pageX + 10) + 'px')
                        .style('top', (event.pageY - 10) + 'px');
                })
                .on('mouseout', function() {
                    tooltip.transition().duration(200).style('opacity', 0);
                });
        }
    });

    // Add dots with hover functionality (after exclusion bars so they render on top)
    const dots = svg.selectAll('.dot')
        .data(processedData)
        .enter().append('circle')
        .attr('class', 'dot')
        .attr('cx', d => x(d.date!))
        .attr('cy', d => y(d.calories))
        .attr('r', 4)
        .attr('fill', '#10B981')
        .style('cursor', 'pointer');
    
    dots.on('mouseover', function(event, d) {
            d3.select(this).attr('r', 6);
            
            const formatTooltipDate = d3.timeFormat('%B %d, %Y');
            let tooltipContent = `
                <div style="color: ${textColor};">
                    <div style="font-weight: 600; margin-bottom: 6px;">${formatTooltipDate(d.date)}</div>
                    <div style="margin-bottom: 4px;">Calories: <span style="font-weight: 500;">${Math.round(d.calories)}</span></div>
            `;
            
            if (d.notes && d.notes.length > 0) {
                tooltipContent += `<div style="border-top: 1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}; padding-top: 6px; margin-top: 6px;">`;
                tooltipContent += `<div style="font-weight: 500; margin-bottom: 4px;">Notes:</div>`;
                d.notes.forEach(note => {
                    if (note && note.trim()) {
                        tooltipContent += `<div style="margin-bottom: 2px; font-size: 12px;">• ${note}</div>`;
                    }
                });
                tooltipContent += `</div>`;
            }
            
            tooltipContent += `</div>`;
            
            tooltip.transition().duration(200).style('opacity', 1);
            tooltip.html(tooltipContent)
                .style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mousemove', function(event) {
            tooltip.style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mouseout', function() {
            d3.select(this).attr('r', 4);
            tooltip.transition().duration(200).style('opacity', 0);
        });

    // Add axes with improved styling
    svg.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x).tickFormat(formatDate as any))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    svg.append('g')
        .call(d3.axisLeft(y))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    // Add Y-axis label
    svg.append('text')
        .attr('transform', 'rotate(-90)')
        .attr('y', 0 - margin.left)
        .attr('x', 0 - (height / 2))
        .attr('dy', '1em')
        .style('text-anchor', 'middle')
        .style('fill', textColor)
        .style('font-size', '14px')
        .style('font-weight', '500')
        .text('Calories');
}

function createWeightChart() {
    if (!weightChart.value) return;
    
    const margin = { top: 60, right: 30, bottom: 40, left: 60 };
    const containerWidth = Math.min(weightChart.value.clientWidth, weightChart.value.parentElement?.clientWidth || 800);
    const width = containerWidth - margin.left - margin.right;
    const height = 350 - margin.top - margin.bottom;

    // Clear previous chart and tooltips
    d3.select(weightChart.value).selectAll('*').remove();
    d3.selectAll('.tooltip').remove();

    const svg = d3.select(weightChart.value)
        .append('svg')
        .attr('width', '100%')
        .attr('height', height + margin.top + margin.bottom)
        .attr('viewBox', `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    const data = props.weightStats.weightByDay;
    
    const parseDate = d3.timeParse('%Y-%m-%d');
    const formatDate = d3.timeFormat('%m/%d');
    
    const processedData = data.map(d => ({
        date: parseDate(d.date)!,
        weight: d.weight,
        notes: d.notes
    })).filter(d => d.date !== null);

    const x = d3.scaleTime()
        .domain(d3.extent(processedData, d => d.date) as [Date, Date])
        .range([0, width]);

    const maxWeight = d3.max(processedData, d => d.weight) || 0;
    const minWeight = d3.min(processedData, d => d.weight) || 0;
    
    // Add 30% buffer to top and bottom, rounded to nearest whole number
    const buffer = (maxWeight - minWeight) * 0.3;
    const yMin = Math.round(minWeight - buffer);
    const yMax = Math.round(maxWeight + buffer);
    
    const y = d3.scaleLinear()
        .domain([yMin, yMax])
        .range([height, 0]);

    // Determine if we're in dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#F3F4F6' : '#374151';
    const gridColor = isDarkMode ? '#6B7280' : '#E5E7EB';
    const axisColor = isDarkMode ? '#9CA3AF' : '#9CA3AF';

    // Add diet period backgrounds
    const phaseColors = {
        cut: isDarkMode ? 'rgba(239, 68, 68, 0.1)' : 'rgba(239, 68, 68, 0.05)',
        bulk: isDarkMode ? 'rgba(34, 197, 94, 0.1)' : 'rgba(34, 197, 94, 0.05)', 
        maintenance: isDarkMode ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)'
    };

    props.dietPeriods.forEach(period => {
        const startX = x(parseDate(period.start_date)!);
        const endX = x(parseDate(period.end_date)!);
        const color = phaseColors[period.phase_type as keyof typeof phaseColors];
        
        // Add background rectangle for period
        svg.append('rect')
            .attr('x', startX)
            .attr('y', 0)
            .attr('width', endX - startX)
            .attr('height', height)
            .attr('fill', color)
            .attr('opacity', 0.3);
            
        // Add period label at the top
        if (endX - startX > 80) { // Only show label if there's enough space
            svg.append('text')
                .attr('x', startX + (endX - startX) / 2)
                .attr('y', -10)
                .style('text-anchor', 'middle')
                .style('fill', textColor)
                .style('font-size', '12px')
                .style('font-weight', '500')
                .text(`${period.name} (${period.phase_type})`);
        }
    });

    // Create line generator
    const line = d3.line<any>()
        .x(d => x(d.date))
        .y(d => y(d.weight))
        .curve(d3.curveMonotoneX);

    // Calculate linear regression for weight
    const weightRegression = calculateLinearRegression(processedData.map(d => [d.date.getTime(), d.weight]));
    const weightRegressionLine = processedData.map(d => ({
        date: d.date,
        value: weightRegression.slope * d.date.getTime() + weightRegression.intercept
    }));

    // Add regression line first (so it appears behind the main line)
    svg.append('path')
        .datum(weightRegressionLine)
        .attr('fill', 'none')
        .attr('stroke', '#3B82F6')
        .attr('stroke-width', 1)
        .attr('stroke-dasharray', '5,5')
        .attr('opacity', 0.7)
        .attr('d', d3.line<any>()
            .x(d => x(d.date))
            .y(d => y(d.value))
        );

    // Add the main line
    svg.append('path')
        .datum(processedData)
        .attr('fill', 'none')
        .attr('stroke', '#3B82F6')
        .attr('stroke-width', 2)
        .attr('d', line);

    // Create tooltip div
    const tooltip = d3.select('body')
        .append('div')
        .attr('class', 'tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('background', isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)')
        .style('border', `1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}`)
        .style('border-radius', '8px')
        .style('padding', '12px')
        .style('font-size', '14px')
        .style('box-shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.1)')
        .style('backdrop-filter', 'blur(8px)')
        .style('z-index', '1000')
        .style('pointer-events', 'none');

    // Add dots with hover functionality
    svg.selectAll('.dot')
        .data(processedData)
        .enter().append('circle')
        .attr('class', 'dot')
        .attr('cx', d => x(d.date!))
        .attr('cy', d => y(d.weight))
        .attr('r', 4)
        .attr('fill', '#3B82F6')
        .style('cursor', 'pointer')
        .on('mouseover', function(event, d) {
            d3.select(this).attr('r', 6);
            
            const formatTooltipDate = d3.timeFormat('%B %d, %Y');
            let tooltipContent = `
                <div style="color: ${textColor};">
                    <div style="font-weight: 600; margin-bottom: 6px;">${formatTooltipDate(d.date)}</div>
                    <div style="margin-bottom: 4px;">Weight: <span style="font-weight: 500;">${Math.round(d.weight * 10) / 10} lbs</span></div>
            `;
            
            if (d.notes && d.notes.trim()) {
                tooltipContent += `<div style="border-top: 1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}; padding-top: 6px; margin-top: 6px;">`;
                tooltipContent += `<div style="font-weight: 500; margin-bottom: 4px;">Notes:</div>`;
                tooltipContent += `<div style="font-size: 12px;">${d.notes}</div>`;
                tooltipContent += `</div>`;
            }
            
            tooltipContent += `</div>`;
            
            tooltip.transition().duration(200).style('opacity', 1);
            tooltip.html(tooltipContent)
                .style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mousemove', function(event) {
            tooltip.style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mouseout', function() {
            d3.select(this).attr('r', 4);
            tooltip.transition().duration(200).style('opacity', 0);
        });

    // Add exclusion bars for weight data
    const parseWeightExclusionDate = d3.timeParse('%Y-%m-%d');
    Object.entries(props.weightStats.excludedWeightData).forEach(([dateStr, note]) => {
        const exclusionDate = parseWeightExclusionDate(dateStr);
        if (exclusionDate && x.domain()[0] <= exclusionDate && exclusionDate <= x.domain()[1]) {
            svg.append('rect')
                .attr('class', 'exclusion-bar')
                .attr('x', x(exclusionDate) - 2.5) // Center the 5px bar
                .attr('y', 0)
                .attr('width', 5)
                .attr('height', height)
                .attr('fill', 'rgba(239, 68, 68, 0.6)') // Semi-transparent red
                .style('cursor', 'pointer')
                .on('mouseover', function(event) {
                    const formatTooltipDate = d3.timeFormat('%B %d, %Y');
                    const tooltipContent = `
                        <div style="color: ${textColor};">
                            <div style="font-weight: 600; margin-bottom: 6px; color: #DC2626;">Excluded: ${formatTooltipDate(exclusionDate)}</div>
                            <div style="margin-bottom: 4px;">Weight data excluded from calculations</div>
                            ${note ? `<div style="border-top: 1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}; padding-top: 6px; margin-top: 6px; font-style: italic;">${note}</div>` : ''}
                        </div>
                    `;
                    
                    tooltip.transition().duration(200).style('opacity', 1);
                    tooltip.html(tooltipContent)
                        .style('left', (event.pageX + 10) + 'px')
                        .style('top', (event.pageY - 10) + 'px');
                })
                .on('mousemove', function(event) {
                    tooltip.style('left', (event.pageX + 10) + 'px')
                        .style('top', (event.pageY - 10) + 'px');
                })
                .on('mouseout', function() {
                    tooltip.transition().duration(200).style('opacity', 0);
                });
        }
    });

    // Add axes with improved styling
    svg.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x).tickFormat(formatDate as any))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    svg.append('g')
        .call(d3.axisLeft(y))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    // Add Y-axis label
    svg.append('text')
        .attr('transform', 'rotate(-90)')
        .attr('y', 0 - margin.left)
        .attr('x', 0 - (height / 2))
        .attr('dy', '1em')
        .style('text-anchor', 'middle')
        .style('fill', textColor)
        .style('font-size', '14px')
        .style('font-weight', '500')
        .text('Weight (lbs)');
}

function toggleMacroChart(macro: 'protein' | 'carbs' | 'fat') {
    showMacroCharts.value[macro] = !showMacroCharts.value[macro];
    if (showMacroCharts.value[macro]) {
        // Use nextTick to ensure DOM is updated
        nextTick(() => {
            createMacroChart(macro);
        });
    }
}

function createMacroChart(macro: 'protein' | 'carbs' | 'fat') {
    const chartRef = macro === 'protein' ? proteinChart : macro === 'carbs' ? carbsChart : fatChart;
    if (!chartRef.value || props.nutritionStats.macrosByDay.length === 0) return;
    
    const margin = { top: 60, right: 30, bottom: 40, left: 60 };
    const containerWidth = Math.min(chartRef.value.clientWidth, chartRef.value.parentElement?.clientWidth || 800);
    const width = containerWidth - margin.left - margin.right;
    const height = 300 - margin.top - margin.bottom;

    // Clear previous chart and tooltips
    d3.select(chartRef.value).selectAll('*').remove();

    const svg = d3.select(chartRef.value)
        .append('svg')
        .attr('width', '100%')
        .attr('height', height + margin.top + margin.bottom)
        .attr('viewBox', `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    const data = props.nutritionStats.macrosByDay;
    
    const parseDate = d3.timeParse('%Y-%m-%d');
    const formatDate = d3.timeFormat('%m/%d');
    
    const processedData = data.map(d => ({
        date: parseDate(d.date)!,
        value: d[macro]
    })).filter(d => d.date !== null);

    const x = d3.scaleTime()
        .domain(d3.extent(processedData, d => d.date) as [Date, Date])
        .range([0, width]);

    const maxValue = d3.max(processedData, d => d.value) || 0;
    const minValue = d3.min(processedData, d => d.value) || 0;
    
    // Add 10% buffer to top and bottom
    const buffer = (maxValue - minValue) * 0.1;
    const yMin = Math.max(0, Math.round(minValue - buffer));
    const yMax = Math.round(maxValue + buffer);
    
    const y = d3.scaleLinear()
        .domain([yMin, yMax])
        .range([height, 0]);

    // Determine colors and labels
    const colors = {
        protein: '#3b82f6', // Blue
        carbs: '#10b981',   // Green
        fat: '#f59e0b'      // Yellow
    };
    
    const labels = {
        protein: 'Protein (g)',
        carbs: 'Carbs (g)', 
        fat: 'Fat (g)'
    };

    // Determine if we're in dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#F3F4F6' : '#374151';
    const gridColor = isDarkMode ? '#6B7280' : '#E5E7EB';
    const axisColor = isDarkMode ? '#9CA3AF' : '#9CA3AF';

    // Create line generator
    const line = d3.line<any>()
        .x(d => x(d.date))
        .y(d => y(d.value))
        .curve(d3.curveMonotoneX);

    // Calculate linear regression for macro
    const macroRegression = calculateLinearRegression(processedData.map(d => [d.date.getTime(), d.value]));
    const macroRegressionLine = processedData.map(d => ({
        date: d.date,
        value: macroRegression.slope * d.date.getTime() + macroRegression.intercept
    }));

    // Add regression line first (so it appears behind the main line)
    svg.append('path')
        .datum(macroRegressionLine)
        .attr('fill', 'none')
        .attr('stroke', colors[macro])
        .attr('stroke-width', 1)
        .attr('stroke-dasharray', '5,5')
        .attr('opacity', 0.7)
        .attr('d', d3.line<any>()
            .x(d => x(d.date))
            .y(d => y(d.value))
        );

    // Add the main line
    svg.append('path')
        .datum(processedData)
        .attr('fill', 'none')
        .attr('stroke', colors[macro])
        .attr('stroke-width', 2)
        .attr('d', line);

    // Create tooltip div
    const tooltip = d3.select('body')
        .append('div')
        .attr('class', 'tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('background', isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)')
        .style('border', `1px solid ${isDarkMode ? '#4B5563' : '#E5E7EB'}`)
        .style('border-radius', '8px')
        .style('padding', '12px')
        .style('font-size', '14px')
        .style('box-shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.1)')
        .style('backdrop-filter', 'blur(8px)')
        .style('z-index', '1000')
        .style('pointer-events', 'none');

    // Add dots with hover functionality
    const dots = svg.selectAll('.dot')
        .data(processedData)
        .enter().append('circle')
        .attr('class', 'dot')
        .attr('cx', d => x(d.date!))
        .attr('cy', d => y(d.value))
        .attr('r', 4)
        .attr('fill', colors[macro])
        .style('cursor', 'pointer');
    
    dots.on('mouseover', function(event, d) {
            d3.select(this).attr('r', 6);
            
            const formatTooltipDate = d3.timeFormat('%B %d, %Y');
            const tooltipContent = `
                <div style="color: ${textColor};">
                    <div style="font-weight: 600; margin-bottom: 6px;">${formatTooltipDate(d.date)}</div>
                    <div style="margin-bottom: 4px;">${labels[macro]}: <span style="font-weight: 500;">${Math.round(d.value * 10) / 10}</span></div>
                </div>
            `;
            
            tooltip.transition().duration(200).style('opacity', 1);
            tooltip.html(tooltipContent)
                .style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mousemove', function(event) {
            tooltip.style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
        })
        .on('mouseout', function() {
            d3.select(this).attr('r', 4);
            tooltip.transition().duration(200).style('opacity', 0);
        });

    // Add axes with improved styling
    svg.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x).tickFormat(formatDate as any))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    svg.append('g')
        .call(d3.axisLeft(y))
        .call(g => {
            g.selectAll('text')
                .style('fill', textColor)
                .style('font-size', '12px');
            g.selectAll('line')
                .style('stroke', gridColor);
            g.select('.domain')
                .style('stroke', axisColor);
        });

    // Add Y-axis label
    svg.append('text')
        .attr('transform', 'rotate(-90)')
        .attr('y', 0 - margin.left)
        .attr('x', 0 - (height / 2))
        .attr('dy', '1em')
        .style('text-anchor', 'middle')
        .style('fill', textColor)
        .style('font-size', '14px')
        .style('font-weight', '500')
        .text(labels[macro]);
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Life Tracker Dashboard
                    </h2>
                </div>
                <div class="flex gap-4">
                    <!-- View Type Toggle -->
                    <div class="flex rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden">
                        <button
                            @click="changeDietPeriod(props.dietPeriods[0]?.id?.toString() || '')"
                            :class="[
                                'px-3 py-2 text-sm font-medium transition-colors',
                                currentViewType === 'diet-period' 
                                    ? 'bg-blue-600 text-white' 
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            Diet Period
                        </button>
                        <button
                            @click="changeMonth(currentMonth)"
                            :class="[
                                'px-3 py-2 text-sm font-medium transition-colors border-l border-gray-300 dark:border-gray-600',
                                currentViewType === 'month' 
                                    ? 'bg-blue-600 text-white' 
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            Month
                        </button>
                    </div>

                    <!-- Month Selector (when in month view) -->
                    <select 
                        v-if="currentViewType === 'month'"
                        :value="currentMonth" 
                        @change="changeMonth(($event.target as HTMLSelectElement).value)"
                        class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                            {{ month.label }}
                        </option>
                    </select>

                    <!-- Diet Period Selector (when in diet period view) -->
                    <select 
                        v-if="currentViewType === 'diet-period'"
                        :value="selectedDietPeriodId?.toString() || ''" 
                        @change="changeDietPeriod(($event.target as HTMLSelectElement).value)"
                        class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="">All Time</option>
                        <option v-for="period in dietPeriods" :key="period.id" :value="period.id.toString()">
                            {{ period.name }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Daily Calories</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ Math.round(nutritionStats.averageDailyCalories) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Weight</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ weightStats.weightByDay.length > 0 ? Math.round(weightStats.weightByDay[weightStats.weightByDay.length - 1].weight * 10) / 10 : 'N/A' }} lbs
                        </p>
                    </div>
                </div>

                <!-- Charts -->
                <div class="space-y-8">
                    <!-- Calories Chart -->
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Calories by Day</h3>
                        <div ref="calorieChart" class="w-full overflow-hidden"></div>
                        <div v-if="nutritionStats.caloriesByDay.length === 0" class="text-center text-gray-500 py-8">
                            No calorie data for this month
                        </div>
                    </div>

                    <!-- Weight Chart -->
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Weight by Day</h3>
                        <div ref="weightChart" class="w-full overflow-hidden"></div>
                        <div v-if="weightStats.weightByDay.length === 0" class="text-center text-gray-500 py-8">
                            No weight data for this month
                        </div>
                    </div>

                    <!-- Macro Charts -->
                    <div v-if="nutritionStats.macrosByDay.length > 0" class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Macro Charts</h3>
                            <div class="flex gap-2">
                                <SecondaryButton 
                                    @click="toggleMacroChart('protein')"
                                    :class="{ 'bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300': showMacroCharts.protein }"
                                >
                                    Protein
                                </SecondaryButton>
                                <SecondaryButton 
                                    @click="toggleMacroChart('carbs')"
                                    :class="{ 'bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300': showMacroCharts.carbs }"
                                >
                                    Carbs
                                </SecondaryButton>
                                <SecondaryButton 
                                    @click="toggleMacroChart('fat')"
                                    :class="{ 'bg-yellow-100 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-300': showMacroCharts.fat }"
                                >
                                    Fat
                                </SecondaryButton>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div v-if="showMacroCharts.protein">
                                <h4 class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2">Protein by Day</h4>
                                <div ref="proteinChart" class="w-full overflow-hidden"></div>
                            </div>
                            <div v-if="showMacroCharts.carbs">
                                <h4 class="text-sm font-medium text-green-600 dark:text-green-400 mb-2">Carbs by Day</h4>
                                <div ref="carbsChart" class="w-full overflow-hidden"></div>
                            </div>
                            <div v-if="showMacroCharts.fat">
                                <h4 class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-2">Fat by Day</h4>
                                <div ref="fatChart" class="w-full overflow-hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>