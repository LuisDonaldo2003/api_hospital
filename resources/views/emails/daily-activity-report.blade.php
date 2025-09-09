<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Actividades - Hospital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
        }
        
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header h1 {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }
        
        .header .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 400;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .stat-number.trend-up {
            color: #48bb78;
        }
        
        .stat-number.trend-down {
            color: #f56565;
        }
        
        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .section-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .activities-grid {
            display: grid;
            gap: 12px;
        }
        
        .activity-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .activity-row:hover {
            background: #f1f5f9;
            border-color: #cbd5e0;
        }
        
        .activity-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 15px;
        }
        
        .activity-metrics {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .activity-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }
        
        .activity-percentage {
            color: #718096;
            font-size: 13px;
            font-weight: 500;
            min-width: 45px;
            text-align: right;
        }
        
        .users-list {
            display: grid;
            gap: 12px;
        }
        
        .user-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .user-row:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .user-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 15px;
        }
        
        .user-email {
            font-size: 12px;
            color: #718096;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            min-width: 35px;
            text-align: center;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
            border: 1px solid #f6ad55;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .highlight-title {
            font-size: 14px;
            font-weight: 600;
            color: #c05621;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .highlight-value {
            font-size: 22px;
            font-weight: 800;
            color: #9c4221;
        }
        
        .trend-box {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border: 1px solid #68d391;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }
        
        .trend-box.negative {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-color: #fc8181;
        }
        
        .trend-box.stable {
            background: linear-gradient(135deg, #f7fafc 0%, #e2e8f0 100%);
            border-color: #a0aec0;
        }
        
        .trend-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .trend-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .trend-subtext {
            font-size: 13px;
            opacity: 0.8;
        }
        
        .footer {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer-content {
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer-title {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .footer-subtitle {
            opacity: 0.8;
            font-size: 12px;
        }
        
        /* Responsive Design */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .activity-row,
            .user-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .activity-metrics {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>{{ $reportData['report_label'] ?? 'Reporte de Actividades' }}</h1>
                <p class="subtitle">
                    {{ $reportData['report_type'] === 'current' ? 'Resumen hasta las ' . $reportData['current_time'] . ' del ' . $reportData['formatted_date'] : 'Resumen del ' . $reportData['formatted_date'] }}
                </p>
                @if($reportData['is_automated'] ?? false)
                    <p style="font-size: 14px; opacity: 0.8; margin-top: 5px;">
                        🕛 Reporte automático generado a las 12:00 AM - Hora de Ciudad de México
                    </p>
                @endif
                @if($reportData['is_missed_report'] ?? false)
                    <p style="font-size: 14px; background: #fff3cd; padding: 8px; border-radius: 4px; margin-top: 5px; color: #856404;">
                        🔄 Reporte recuperado - Enviado automáticamente al detectar que se había perdido
                    </p>
                @endif
            </div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($reportData['total_activities']) }}</div>
                    <div class="stat-label">Total Actividades</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $reportData['active_users_count'] }}</div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $reportData['hourly_stats']['total_hours_active'] }}</div>
                    <div class="stat-label">Horas Activas</div>
                </div>
                @if($reportData['period_comparison']['trend'] !== 'stable')
                <div class="stat-card">
                    <div class="stat-number {{ $reportData['period_comparison']['trend'] === 'up' ? 'trend-up' : 'trend-down' }}">
                        {{ $reportData['period_comparison']['percentage_change'] > 0 ? '+' : '' }}{{ $reportData['period_comparison']['percentage_change'] }}%
                    </div>
                    <div class="stat-label">Cambio vs Ayer</div>
                </div>
                @endif
            </div>
            
            <!-- Peak Hour Highlight -->
            @if($reportData['hourly_stats']['peak_hour'])
            <div class="highlight-box">
                <div class="highlight-title">🚀 Hora Pico de Actividad</div>
                <div class="highlight-value">
                    {{ $reportData['hourly_stats']['peak_hour']['formatted_hour'] }} 
                    ({{ $reportData['hourly_stats']['peak_hour']['count'] }} actividades)
                </div>
            </div>
            @endif
            
            <!-- Activities by Type -->
            @if($reportData['activities_by_type']->count() > 0)
            <div class="section">
                <div class="section-header">
                    <span class="section-icon">🔍</span>
                    <h2 class="section-title">Actividades por Tipo</h2>
                </div>
                <div class="activities-grid">
                    @foreach($reportData['activities_by_type'] as $activity)
                    <div class="activity-row">
                        <div class="activity-name">{{ ucfirst($activity['type'] ?: 'Sin clasificar') }}</div>
                        <div class="activity-metrics">
                            <span class="activity-badge">{{ $activity['count'] }}</span>
                            <span class="activity-percentage">{{ $activity['percentage'] }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Top Active Users -->
            @if($reportData['top_users']->count() > 0)
            <div class="section">
                <div class="section-header">
                    <span class="section-icon">👥</span>
                    <h2 class="section-title">Usuarios Más Activos</h2>
                </div>
                <div class="users-list">
                    @foreach($reportData['top_users'] as $user)
                    <div class="user-row">
                        <div class="user-info">
                            <div class="user-name">{{ $user->name }}</div>
                            <div class="user-email">{{ $user->email }}</div>
                        </div>
                        <div class="user-badge">{{ $user->activity_count }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Trend Analysis -->
            <div class="section">
                <div class="section-header">
                    <span class="section-icon">📈</span>
                    <h2 class="section-title">Análisis de Tendencia</h2>
                </div>
                <div class="trend-box {{ $reportData['period_comparison']['trend'] === 'up' ? '' : ($reportData['period_comparison']['trend'] === 'down' ? 'negative' : 'stable') }}">
                    <div class="trend-icon">
                        @if($reportData['period_comparison']['trend'] === 'up')
                            📈
                        @elseif($reportData['period_comparison']['trend'] === 'down')
                            📉
                        @else
                            ➡️
                        @endif
                    </div>
                    <div class="trend-text">
                        @if($reportData['period_comparison']['trend'] === 'up')
                            Incremento del {{ $reportData['period_comparison']['percentage_change'] }}%
                        @elseif($reportData['period_comparison']['trend'] === 'down')
                            Disminución del {{ abs($reportData['period_comparison']['percentage_change']) }}%
                        @else
                            Actividad Estable
                        @endif
                    </div>
                    <div class="trend-subtext">
                        @if($reportData['period_comparison']['trend'] === 'up')
                            {{ $reportData['period_comparison']['difference'] }} actividades más que ayer
                        @elseif($reportData['period_comparison']['trend'] === 'down')
                            {{ abs($reportData['period_comparison']['difference']) }} actividades menos que ayer
                        @else
                            Mismo nivel de actividad que ayer
                        @endif
                        ({{ $reportData['period_comparison']['previous_total'] }})
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-title">Sistema de Gestión Hospitalaria</div>
                <div class="footer-subtitle">
                    Reporte generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
