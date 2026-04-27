<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error Tracker' }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

        [x-cloak] {
            display: none !important;
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
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .page-title-line {
            display: flex;
            align-items: baseline;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .page-title-app-name {
            color: var(--muted);
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
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

      .quick-filters {
          display: grid;
          gap: 12px;
      }

      .quick-filter-group {
          display: grid;
          grid-template-columns: 72px 1fr;
          gap: 10px;
          align-items: center;
      }

      .quick-filter-label {
          color: var(--muted);
          font-size: 12px;
          font-weight: 800;
          letter-spacing: 0.08em;
          line-height: 1;
          text-transform: uppercase;
      }

      .quick-filter-options {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
          min-width: 0;
      }

      .quick-filter-chip {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          min-height: 30px;
          border: 1px solid transparent;
          border-radius: 8px;
          color: #475569;
          font-size: 13px;
          font-weight: 700;
          line-height: 1;
          padding: 7px 10px;
          text-decoration: none;
          transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
      }

      .quick-filter-chip:hover {
          background: #f1f5f9;
          color: var(--text);
          text-decoration: none;
      }

      .quick-filter-chip.is-active {
          background: #dbeafe;
          border-color: #bfdbfe;
          color: #1e3a8a;
      }

      .quick-filter-count {
          color: inherit;
          font-size: 12px;
          font-weight: 800;
          opacity: 0.8;
      }

      .filters-search-form {
          border-top: 1px solid var(--border);
          margin-top: 16px;
          padding-top: 16px;
      }

      .sr-only {
          position: absolute;
          width: 1px;
          height: 1px;
          padding: 0;
          margin: -1px;
          overflow: hidden;
          clip: rect(0, 0, 0, 0);
          white-space: nowrap;
          border: 0;
      }

      .issues-workspace {
          display: grid;
          grid-template-columns: 260px minmax(0, 1fr);
          gap: 28px;
          align-items: start;
      }

      .issues-sidebar {
          position: sticky;
          top: 18px;
      }

      .filter-sidebar-stack {
          display: grid;
          gap: 24px;
      }

      .filter-sidebar-section {
          min-width: 0;
      }

      .filter-sidebar-heading {
          margin: 0 0 10px;
          color: #64748b;
          font-size: 12px;
          font-weight: 900;
          letter-spacing: 0.1em;
          line-height: 1;
          text-transform: uppercase;
      }

      .filter-sidebar-options {
          display: grid;
          gap: 5px;
      }

      .filter-sidebar-options.compact {
          grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .filter-sidebar-link {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 14px;
          min-height: 38px;
          border: 1px solid transparent;
          border-radius: 10px;
          color: #475569;
          font-size: 14px;
          font-weight: 700;
          line-height: 1;
          padding: 10px 12px;
          text-decoration: none;
          transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
      }

      .filter-sidebar-link:hover {
          background: #ffffff;
          border-color: var(--border);
          color: var(--text);
          text-decoration: none;
          transform: translateX(1px);
      }

      .filter-sidebar-link.is-active {
          background: #ffffff;
          border-color: #bfdbfe;
          color: #1d4ed8;
          box-shadow: var(--shadow-sm);
      }

      .filter-sidebar-count {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-width: 28px;
          min-height: 22px;
          border-radius: 999px;
          background: #e2e8f0;
          color: #475569;
          font-size: 12px;
          font-weight: 900;
          padding: 3px 7px;
      }

      .filter-sidebar-link.is-active .filter-sidebar-count {
          background: #dbeafe;
          color: #1d4ed8;
      }

      .filter-sidebar-form select {
          min-height: 40px;
          border-radius: 10px;
          padding: 9px 12px;
      }

      .filter-sidebar-muted-row {
          border: 1px solid var(--border);
          border-radius: 10px;
          background: #ffffff;
          color: var(--muted);
          font-size: 14px;
          font-weight: 700;
          padding: 12px;
      }

      .issues-main {
          min-width: 0;
      }

      .issues-topbar {
          display: grid;
          grid-template-columns: minmax(320px, 1fr) auto;
          gap: 18px;
          align-items: center;
          margin-bottom: 18px;
      }

      .issues-search-form {
          min-width: 0;
      }

      .issues-search-control {
          position: relative;
          display: flex;
          align-items: center;
      }

      .issues-search-control svg {
          position: absolute;
          left: 16px;
          width: 20px;
          height: 20px;
          color: #94a3b8;
          pointer-events: none;
      }

      .issues-search-control input {
          min-height: 54px;
          border-radius: 16px;
          padding-left: 48px;
          font-size: 15px;
          box-shadow: var(--shadow-sm);
      }

      .sort-segmented {
          display: inline-flex;
          align-items: center;
          min-height: 50px;
          border: 1px solid var(--border-strong);
          border-radius: 16px;
          background: #ffffff;
          padding: 4px;
          box-shadow: var(--shadow-sm);
      }

      .sort-segment {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-height: 40px;
          min-width: 96px;
          border-radius: 12px;
          color: #64748b;
          font-size: 14px;
          font-weight: 800;
          padding: 0 14px;
          text-decoration: none;
          transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
      }

      .sort-segment:hover {
          color: var(--text);
          text-decoration: none;
      }

      .sort-segment.is-active {
          background: #eff6ff;
          color: #1d4ed8;
          box-shadow: var(--shadow-sm);
      }

      .period-segmented {
          display: inline-flex;
          align-items: center;
          min-height: 48px;
          border: 1px solid var(--border-strong);
          border-radius: 14px;
          background: #ffffff;
          padding: 4px;
          box-shadow: var(--shadow-sm);
      }

      .period-segment {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-height: 38px;
          min-width: 58px;
          border-radius: 10px;
          color: #475569;
          font-size: 14px;
          font-weight: 800;
          line-height: 1;
          padding: 0 12px;
          text-decoration: none;
          transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
      }

      .period-segment:hover {
          color: var(--text);
          text-decoration: none;
      }

      .period-segment.is-active {
          background: #eff6ff;
          color: #1d4ed8;
          box-shadow: var(--shadow-sm);
      }

      .issue-card-list {
          display: grid;
          gap: 14px;
      }

      .issue-card-item {
          display: grid;
          grid-template-columns: minmax(0, 1fr) 184px;
          gap: 24px;
          align-items: stretch;
          background: #ffffff;
          border: 1px solid var(--border);
          border-radius: 18px;
          padding: 22px 24px;
          box-shadow: var(--shadow-sm);
          transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
      }

      .issue-card-item:hover {
          border-color: #cbd5e1;
          box-shadow: var(--shadow-md);
          transform: translateY(-1px);
      }

      .issue-card-content {
          min-width: 0;
      }

      .issue-card-meta-row,
      .issue-card-footer-row {
          display: flex;
          align-items: center;
          gap: 10px;
          flex-wrap: wrap;
      }

      .issue-card-meta-row {
          color: var(--muted);
          font-size: 13px;
          font-weight: 700;
          margin-bottom: 14px;
      }

      .issue-status-badge {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-height: 28px;
          border: 1px solid transparent;
          border-radius: 999px;
          font-size: 11px;
          font-weight: 900;
          letter-spacing: 0.08em;
          line-height: 1;
          padding: 6px 10px;
      }

      .issue-status-badge.is-open {
          background: #fff1f2;
          border-color: #fecdd3;
          color: #be123c;
      }

      .issue-status-badge.is-resolved {
          background: #f0fdf4;
          border-color: #bbf7d0;
          color: #15803d;
      }

      .issue-status-badge.is-ignored {
          background: #fffbeb;
          border-color: #fde68a;
          color: #b45309;
      }

      .issue-status-badge.is-muted {
          background: #f5f3ff;
          border-color: #ddd6fe;
          color: #6d28d9;
      }

      .issue-status-badge.is-neutral {
          background: #f8fafc;
          border-color: #cbd5e1;
          color: #475569;
      }

      .issue-card-title {
          display: inline-block;
          max-width: 100%;
          color: var(--text);
          font-size: 22px;
          font-weight: 850;
          line-height: 1.22;
          text-decoration: none;
      }

      .issue-card-title:hover {
          color: var(--link);
          text-decoration: none;
      }

      .issue-card-message {
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          margin: 9px 0 16px;
          max-width: 920px;
          overflow: hidden;
          color: #64748b;
          font-size: 15px;
          line-height: 1.55;
      }

      .issue-location-chip {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          min-width: 0;
          max-width: 100%;
          border-radius: 10px;
          background: #f1f5f9;
          color: #475569;
          font-size: 13px;
          font-weight: 700;
          line-height: 1.2;
          padding: 8px 10px;
      }

      .issue-location-chip svg {
          flex: 0 0 auto;
          width: 17px;
          height: 17px;
          color: #64748b;
      }

      .issue-card-trend {
          display: inline-flex;
          align-items: end;
          gap: 2px;
          height: 24px;
      }

      .issue-card-trend span {
          display: block;
          width: 3px;
          min-height: 3px;
          border-radius: 999px 999px 2px 2px;
          background: #60a5fa;
          opacity: 0.75;
      }

      .issue-card-side {
          display: flex;
          flex-direction: column;
          align-items: flex-end;
          justify-content: space-between;
          gap: 18px;
          min-width: 0;
      }

      .issue-card-count {
          text-align: right;
      }

      .issue-card-count strong {
          display: block;
          color: var(--text);
          font-size: 30px;
          font-weight: 900;
          line-height: 1;
      }

      .issue-card-count span {
          display: block;
          margin-top: 6px;
          color: var(--muted);
          font-size: 12px;
          font-weight: 900;
          letter-spacing: 0.08em;
          text-transform: uppercase;
      }

      .issue-card-actions {
          display: flex;
          justify-content: flex-end;
          gap: 8px;
          flex-wrap: wrap;
      }

      .issue-card-actions form {
          margin: 0;
      }

      .issue-action-button {
          min-height: 38px;
          border: 1px solid transparent;
          border-radius: 11px;
          background: transparent;
          color: #64748b;
          cursor: pointer;
          font: inherit;
          font-size: 13px;
          font-weight: 800;
          line-height: 1;
          padding: 10px 13px;
          transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
      }

      .issue-action-button:hover {
          background: #f8fafc;
          color: var(--text);
      }

      .issue-action-button.primary {
          background: #eff6ff;
          border-color: #bfdbfe;
          color: #1d4ed8;
      }

      .issue-action-button.primary:hover {
          background: #dbeafe;
          border-color: #93c5fd;
      }

      .issues-empty-state {
          background: #ffffff;
          border: 1px dashed var(--border-strong);
          border-radius: 18px;
          padding: 34px;
          text-align: center;
      }

      .issues-empty-state h2 {
          margin: 0;
          font-size: 18px;
      }

      .issues-empty-state p {
          margin: 8px 0 0;
          color: var(--muted);
      }

      .issues-pagination {
          margin-top: 18px;
      }

      .filters-grid-primary {
          display: grid;
          grid-template-columns: repeat(3, minmax(180px, 1fr));
          gap: 12px;
          align-items: start;
      }

      .filters-grid-secondary {
          display: grid;
          grid-template-columns: minmax(320px, 1.8fr) minmax(180px, 1fr) auto auto;
          gap: 12px;
          align-items: end;
          margin-top: 12px;
      }

      .filter-field {
          min-width: 0;
      }

      .filter-actions {
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

      .filter-control-disabled:disabled {
          background: #f1f5f9;
          border-color: var(--border);
          color: var(--muted);
          cursor: not-allowed;
          opacity: 1;
      }

      .multi-select {
          position: relative;
      }

      .multi-select > summary {
          list-style: none;
      }

      .multi-select > summary::-webkit-details-marker {
          display: none;
      }

      .multi-select-summary {
          display: flex;
          align-items: center;
          justify-content: space-between;
          min-height: 48px;
          width: 100%;
          background: var(--input-bg);
          color: var(--text);
          border: 1px solid var(--input-border);
          border-radius: 12px;
          padding: 12px 40px 12px 14px;
          cursor: pointer;
          line-height: 1.25;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          position: relative;
      }

      .multi-select-summary::after {
          content: '';
          position: absolute;
          right: 16px;
          width: 9px;
          height: 9px;
          border-right: 2px solid var(--text);
          border-bottom: 2px solid var(--text);
          transform: rotate(45deg) translateY(-2px);
      }

      .multi-select[open] .multi-select-summary {
          border-color: #93c5fd;
          box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
      }

      .multi-select[open] .multi-select-summary::after {
          transform: rotate(225deg) translate(-2px, -2px);
      }

      .multi-select-panel {
          position: absolute;
          top: calc(100% + 6px);
          left: 0;
          right: 0;
          z-index: 40;
          display: grid;
          gap: 4px;
          background: #ffffff;
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 8px;
          box-shadow: var(--shadow-sm), var(--shadow-md);
      }

      .multi-select-option {
          display: flex;
          align-items: center;
          gap: 8px;
          min-height: 34px;
          padding: 8px 10px;
          border-radius: 8px;
          cursor: pointer;
          font-weight: 600;
      }

      .multi-select-option:hover {
          background: #f8fafc;
      }

      .multi-select-option input {
          width: auto;
          margin: 0;
          box-shadow: none;
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

      .mini-trend {
          display: inline-flex;
          align-items: end;
          gap: 2px;
          width: 112px;
          height: 28px;
          padding: 2px 0;
          vertical-align: middle;
      }

      .mini-trend-bar {
          display: block;
          width: 3px;
          min-height: 3px;
          border-radius: 999px 999px 2px 2px;
          background: #2563eb;
          opacity: 0.72;
      }

      .mini-trend-empty {
          width: 112px;
          height: 1px;
          border-top: 1px dashed var(--border-strong);
      }

      @media (max-width: 1024px) {
          .issues-workspace {
              grid-template-columns: 1fr;
          }

          .issues-sidebar {
              position: static;
          }

          .filter-sidebar-stack {
              grid-template-columns: repeat(2, minmax(0, 1fr));
          }

          .issues-topbar {
              grid-template-columns: 1fr;
          }

          .sort-segmented {
              justify-self: start;
              width: 100%;
          }

          .period-segmented {
              width: 100%;
          }

          .sort-segment {
              flex: 1 1 0;
          }

          .period-segment {
              flex: 1 1 0;
          }

          .filters-grid-primary {
              grid-template-columns: 1fr 1fr;
          }

          .filters-grid-secondary {
              grid-template-columns: 1fr 1fr;
          }

          .filter-field-search {
              grid-column: 1 / -1;
          }
      }

      @media (max-width: 640px) {
          .filter-sidebar-stack {
              grid-template-columns: 1fr;
          }

          .issue-card-item {
              grid-template-columns: 1fr;
              padding: 18px;
          }

          .issue-card-side {
              align-items: flex-start;
              border-top: 1px solid var(--border);
              padding-top: 16px;
          }

          .issue-card-count {
              text-align: left;
          }

          .issue-card-actions {
              justify-content: flex-start;
              width: 100%;
          }

          .issue-card-actions form,
          .issue-action-button {
              width: 100%;
          }

          .sort-segment {
              min-width: 0;
              padding: 0 10px;
          }

          .period-segment {
              min-width: 0;
              padding: 0 9px;
          }

          .quick-filter-group {
              grid-template-columns: 1fr;
              gap: 8px;
          }

          .filters-grid-primary,
          .filters-grid-secondary {
              grid-template-columns: 1fr;
          }

          .filter-field-search {
              grid-column: auto;
          }

          .filter-actions {
              flex-direction: column;
              align-items: stretch;
          }

          .filter-actions .btn {
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
          gap: 0;
      }

      .tabs-container {
          margin-bottom: 18px;
      }

      .tabs-content {
          padding-top: 18px;
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

      .stacktrace-shell {
          display: flex;
          flex-direction: column;
          gap: 12px;
      }

      .stacktrace-summary {
          display: flex;
          justify-content: space-between;
          gap: 18px;
          align-items: flex-start;
          padding: 16px;
          border: 1px solid var(--border);
          border-radius: 12px;
          background: #ffffff;
      }

      .stacktrace-exception {
          font-weight: 800;
          color: var(--text);
          word-break: break-word;
      }

      .stacktrace-message {
          margin-top: 6px;
          color: var(--muted);
          line-height: 1.6;
          word-break: break-word;
      }

      .stacktrace-order {
          flex: 0 0 auto;
          color: var(--muted);
          font-size: 12px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 0.08em;
      }

      .first-project-frame {
          padding: 10px 12px;
          border: 1px solid #bfdbfe;
          border-radius: 10px;
          background: #eff6ff;
          color: #1e3a8a;
          font-size: 13px;
          font-weight: 700;
      }

      .stacktrace-list {
          display: flex;
          flex-direction: column;
          gap: 8px;
      }

      .stacktrace-frame,
      .stacktrace-group {
          border: 1px solid var(--border);
          border-radius: 10px;
          background: #ffffff;
          overflow: hidden;
      }

      .stacktrace-frame {
          padding: 12px 14px;
      }

      .stacktrace-frame-project {
          border-color: #bfdbfe;
          box-shadow: inset 3px 0 0 #2563eb;
      }

      .stacktrace-frame-muted {
          background: #f8fafc;
          border-radius: 0;
          border-width: 1px 0 0;
      }

      .stacktrace-frame-muted:first-child {
          border-top-width: 0;
      }

      .stacktrace-frame-main {
          display: flex;
          gap: 8px;
          align-items: center;
          flex-wrap: wrap;
      }

      .stacktrace-index,
      .stacktrace-classification {
          display: inline-flex;
          align-items: center;
          min-height: 22px;
          padding: 3px 7px;
          border-radius: 999px;
          background: #f1f5f9;
          color: #475569;
          font-size: 11px;
          font-weight: 800;
          line-height: 1;
      }

      .stacktrace-classification {
          text-transform: lowercase;
      }

      .stacktrace-callable {
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
          font-size: 13px;
          font-weight: 800;
          color: var(--text);
          word-break: break-word;
      }

      .stacktrace-location {
          margin-top: 7px;
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
          font-size: 12px;
          color: var(--muted);
          word-break: break-word;
      }

      .stacktrace-group-toggle {
          width: 100%;
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 10px 12px;
          border: 0;
          background: #f8fafc;
          color: var(--muted);
          font: inherit;
          font-size: 13px;
          font-weight: 800;
          cursor: pointer;
          text-align: left;
      }

      .stacktrace-caret {
          width: 16px;
          color: #475569;
      }

      .stacktrace-group-frames {
          border-top: 1px solid var(--border);
      }

      .source-context {
          margin-top: 12px;
          border: 1px solid var(--border);
          border-radius: 10px;
          overflow: hidden;
          background: #0f172a;
      }

      .source-line {
          display: grid;
          grid-template-columns: 52px 1fr;
          gap: 10px;
          min-height: 24px;
          color: #cbd5e1;
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
          font-size: 12px;
          line-height: 1.6;
      }

      .source-line.is-highlighted {
          background: rgba(37, 99, 235, 0.35);
          color: #ffffff;
      }

      .source-line-number {
          padding: 2px 8px;
          text-align: right;
          color: #94a3b8;
          user-select: none;
      }

      .source-line pre {
          margin: 0;
          padding: 2px 10px 2px 0;
          white-space: pre-wrap;
          word-break: break-word;
          font: inherit;
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

          .stacktrace-summary {
              flex-direction: column;
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

      .event-primary-line {
          display: flex;
          align-items: center;
          gap: 8px;
          flex-wrap: wrap;
      }

      .event-link {
          font-weight: 800;
          color: var(--text);
          white-space: nowrap;
      }

      .event-link:hover {
          color: var(--link);
          text-decoration: none;
      }

      .inline-meta {
          margin-top: 5px;
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
          font-size: 12px;
          color: var(--muted);
          white-space: nowrap;
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
