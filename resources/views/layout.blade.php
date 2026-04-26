<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error Tracker' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --card-soft: #f8fafc;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text: #0f172a;
            --muted: #64748b;
            --link: #2563eb;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --pre-bg: #f8fafc;
            --pre-border: #e2e8f0;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 8px 24px rgba(15, 23, 42, 0.06);
            --radius: 16px;
            --radius-sm: 12px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: var(--link);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1360px;
            margin: 0 auto;
            padding: 28px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: var(--shadow-sm), var(--shadow-md);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .section-label {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 12px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .muted {
            color: var(--muted);
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 18px;
        }

        .title-main h1 {
            margin: 8px 0 6px;
            font-size: 32px;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .title-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .table th {
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        input, select, textarea {
            width: 100%;
            background: var(--input-bg);
            color: var(--text);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 12px 14px;
            font: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        textarea {
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid transparent;
            font: inherit;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }

        .btn-success {
            background: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;
            border-color: #15803d;
        }

        .btn-warning {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #ffffff;
        }

        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
        }

        .btn-secondary {
            background: #475569;
            border-color: #475569;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #334155;
            border-color: #334155;
        }

        .btn-purple {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            border-color: #7c3aed;
            color: #ffffff;
        }

        .btn-purple:hover {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            border-color: #6d28d9;
        }

        .btn-outline {
            background: #ffffff;
            border-color: var(--border-strong);
            color: var(--text);
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            text-transform: lowercase;
        }

        .badge-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .badge-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #b45309;
        }

        .badge-info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .badge-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .badge-muted {
            background: #faf5ff;
            border-color: #ddd6fe;
            color: #7c3aed;
        }

        .badge-neutral {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }

        .actions-layout {
            display: grid;
            grid-template-columns: minmax(320px, 420px) 1px minmax(320px, 1fr);
            gap: 24px;
            align-items: start;
        }

        .actions-divider {
            width: 1px;
            background: linear-gradient(to bottom, transparent, #e2e8f0 10%, #e2e8f0 90%, transparent);
            align-self: stretch;
        }

        .panel-soft {
            background: var(--card-soft);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field-label {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
        }

        .field-help {
            font-size: 13px;
            color: var(--muted);
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .status-item {
            background: var(--card-soft);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }

        .status-item-label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .status-item-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(140px, 1fr));
            gap: 14px;
        }

        .stat-card {
            background: var(--card-soft);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .stat-label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .stat-meta {
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            background: var(--pre-bg);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--pre-border);
            color: var(--text);
            overflow-x: auto;
        }

        @media (max-width: 1100px) {
            .actions-layout {
                grid-template-columns: 1fr;
            }

            .actions-divider {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .title-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 18px;
            }

            .title-main h1 {
                font-size: 26px;
            }

            .status-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .inline-actions {
                flex-direction: column;
            }

            .inline-actions form,
            .inline-actions .btn {
                width: 100%;
            }

            .inline-actions .btn {
                justify-content: center;
            }
        }
        .filters-card {
          padding: 20px;
      }

      .filters-grid {
          display: grid;
          grid-template-columns: minmax(320px, 1.8fr) minmax(180px, 0.8fr) minmax(180px, 0.8fr);
          gap: 12px;
          align-items: end;
      }

      .filters-grid-second {
          display: grid;
          grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto auto;
          gap: 12px;
          align-items: end;
          margin-top: 12px;
      }

      .filters-actions {
          display: flex;
          gap: 10px;
          align-items: end;
      }

      .filters-label {
          display: block;
          margin-bottom: 6px;
          font-size: 13px;
          font-weight: 700;
          color: var(--muted);
      }

      .page-title {
          margin: 0;
          font-size: 32px;
          line-height: 1.1;
          letter-spacing: -0.03em;
      }

      .page-subtitle {
          margin: 6px 0 0;
          color: var(--muted);
      }

      .issue-title-link {
          font-weight: 700;
          color: var(--text);
      }

      .issue-title-link:hover {
          color: var(--link);
          text-decoration: none;
      }

      .issue-meta-line {
          margin-top: 6px;
          font-size: 13px;
          color: var(--muted);
      }

      .table-badges {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
      }

      @media (max-width: 1024px) {
          .filters-grid {
              grid-template-columns: 1fr;
          }

          .filters-grid-second {
              grid-template-columns: 1fr 1fr;
          }
      }

      @media (max-width: 640px) {
          .filters-grid-second {
              grid-template-columns: 1fr;
          }

          .filters-actions {
              flex-direction: column;
              align-items: stretch;
          }

          .filters-actions .btn {
              width: 100%;
          }
      }
      .issue-toolbar {
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          align-items: center;
          margin-top: 14px;
      }

      .dropdown {
          position: relative;
      }

      .dropdown > summary {
          list-style: none;
      }

      .dropdown > summary::-webkit-details-marker {
          display: none;
      }

      .dropdown-panel {
          position: absolute;
          top: calc(100% + 8px);
          right: 0;
          min-width: 220px;
          background: #ffffff;
          border: 1px solid var(--border);
          border-radius: 14px;
          box-shadow: var(--shadow-sm), var(--shadow-md);
          padding: 8px;
          z-index: 50;
      }

      .dropdown-item-form {
          margin: 0;
      }

      .dropdown-item-button {
          width: 100%;
          text-align: left;
          background: transparent;
          border: 0;
          border-radius: 10px;
          padding: 10px 12px;
          font: inherit;
          font-weight: 600;
          color: var(--text);
          cursor: pointer;
      }

      .dropdown-item-button:hover {
          background: #f8fafc;
      }

      .modal {
          width: min(560px, calc(100% - 24px));
          border: 0;
          border-radius: 18px;
          padding: 0;
          box-shadow: 0 20px 60px rgba(15, 23, 42, 0.20);
      }

      .modal::backdrop {
          background: rgba(15, 23, 42, 0.45);
      }

      .modal-card {
          padding: 24px;
      }

      .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: start;
          gap: 16px;
          margin-bottom: 18px;
      }

      .modal-title {
          margin: 0;
          font-size: 24px;
          line-height: 1.1;
          letter-spacing: -0.02em;
      }

      .modal-close {
          background: #ffffff;
          border: 1px solid var(--border);
          color: var(--text);
          min-width: 40px;
          min-height: 40px;
          border-radius: 12px;
          cursor: pointer;
          font-size: 18px;
          font-weight: 700;
      }

      .modal-footer {
          display: flex;
          justify-content: flex-end;
          gap: 10px;
          margin-top: 20px;
      }

      .btn-gray {
          background: #e2e8f0;
          border-color: #cbd5e1;
          color: #334155;
      }

      .btn-gray:hover {
          background: #cbd5e1;
          border-color: #94a3b8;
      }
      .tabs-shell {
          display: flex;
          flex-direction: column;
          gap: 18px;
      }

      .tabs-nav {
          display: flex;
          flex-wrap: wrap;
          gap: 10px;
          border-bottom: 1px solid var(--border);
          padding-bottom: 12px;
      }

      .tab-button {
          appearance: none;
          border: 1px solid var(--border);
          background: #ffffff;
          color: var(--muted);
          border-radius: 999px;
          padding: 10px 14px;
          font: inherit;
          font-weight: 700;
          cursor: pointer;
          transition: 0.15s ease;
      }

      .tab-button:hover {
          border-color: #94a3b8;
          color: var(--text);
      }

      .tab-button.is-active {
          background: #0f172a;
          border-color: #0f172a;
          color: #ffffff;
      }

      .tab-panel {
          display: none;
      }

      .tab-panel.is-active {
          display: block;
      }

      .kv-list {
          display: flex;
          flex-direction: column;
          gap: 10px;
      }

      .kv-row {
          display: grid;
          grid-template-columns: minmax(160px, 220px) 1fr;
          gap: 14px;
          align-items: start;
          padding: 12px 14px;
          border: 1px solid var(--border);
          border-radius: 12px;
          background: #ffffff;
      }

      .kv-key {
          font-size: 13px;
          font-weight: 800;
          color: var(--muted);
          letter-spacing: 0.04em;
          text-transform: uppercase;
      }

      .kv-value {
          color: var(--text);
          line-height: 1.6;
          word-break: break-word;
      }

      .kv-group {
          border: 1px solid var(--border);
          border-radius: 12px;
          background: #ffffff;
          overflow: hidden;
      }

      .kv-group > summary {
          cursor: pointer;
          list-style: none;
          padding: 12px 14px;
          font-weight: 700;
          background: #f8fafc;
          border-bottom: 1px solid var(--border);
      }

      .kv-group > summary::-webkit-details-marker {
          display: none;
      }

      .kv-group-body {
          padding: 12px;
      }

      .kv-empty {
          padding: 14px;
          border: 1px dashed var(--border-strong);
          border-radius: 12px;
          color: var(--muted);
          background: #f8fafc;
      }

      .json-toggle-row {
          display: flex;
          justify-content: flex-end;
          margin-bottom: 12px;
      }

      .raw-json-block {
          display: none;
      }

      .raw-json-block.is-visible {
          display: block;
      }

      .pretty-json-block.is-hidden {
          display: none;
      }

      .stack-frame {
          border: 1px solid var(--border);
          border-radius: 14px;
          background: #ffffff;
          overflow: hidden;
      }

      .stack-frame > summary {
          cursor: pointer;
          list-style: none;
          padding: 14px 16px;
          font-weight: 700;
          background: #f8fafc;
          border-bottom: 1px solid var(--border);
      }

      .stack-frame > summary::-webkit-details-marker {
          display: none;
      }

      .stack-frame-body {
          padding: 16px;
      }

      .compact-issue-reference {
          display: flex;
          flex-direction: column;
          gap: 14px;
      }

      .compact-issue-meta {
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          align-items: center;
      }

      .summary-grid-refined {
          display: grid;
          grid-template-columns: 1.3fr 0.9fr;
          gap: 18px;
      }

      .summary-grid-refined .stat-card {
          min-height: 100%;
      }

      .btn-sm {
          min-height: 34px;
          padding: 8px 12px;
          font-size: 13px;
          border-radius: 10px;
      }

      @media (max-width: 900px) {
          .summary-grid-refined {
              grid-template-columns: 1fr;
          }

          .kv-row {
              grid-template-columns: 1fr;
          }
      }
      .page-breadcrumbs {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 10px;
          flex-wrap: wrap;
      }

      .home-link-chip {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 38px;
          height: 38px;
          border-radius: 12px;
          border: 1px solid var(--border);
          background: #ffffff;
          color: var(--text);
          box-shadow: var(--shadow-sm);
          font-size: 18px;
          font-weight: 800;
          text-decoration: none;
          transition: 0.15s ease;
      }

      .home-link-chip:hover {
          background: #f8fafc;
          border-color: #94a3b8;
          color: var(--text);
          text-decoration: none;
          transform: translateY(-1px);
      }

      .refined-table {
          width: 100%;
          border-collapse: separate;
          border-spacing: 0;
      }

      .refined-table th,
      .refined-table td {
          text-align: left;
          padding: 14px 12px;
          border-bottom: 1px solid var(--border);
          vertical-align: top;
      }

      .refined-table th {
          font-size: 12px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 0.08em;
          color: var(--muted);
      }

      .refined-table tbody tr:hover {
          background: #f8fafc;
      }

      .table-cell-title {
          font-weight: 700;
          color: var(--text);
          line-height: 1.4;
      }

      .table-cell-meta {
          margin-top: 4px;
          font-size: 13px;
          color: var(--muted);
          line-height: 1.5;
      }

      .table-cell-stack {
          display: flex;
          flex-direction: column;
          gap: 6px;
      }

      .table-cell-badges {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          align-items: center;
      }

      .table-actions {
          white-space: nowrap;
      }

      .mono-inline {
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
          font-size: 12px;
          color: var(--muted);
          word-break: break-word;
      }

      @media (max-width: 900px) {
          .refined-table {
              display: block;
              overflow-x: auto;
          }
      }
    </style>
</head>
<body>
    <div class="container">
        @if (session('status'))
            <div class="card" style="border-color: #bfdbfe; background: #eff6ff; color: #1e3a8a;">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>