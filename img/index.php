<?php
// Подключение к базе данных
$db = new mysqli('localhost', 'scareader', 'scareader', 'dbscala');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Путь к изображению
$imagePath = 'logomain.png';

// Получение текущего года
$currentYear = date('Y');

// Получение версий для выпадающего списка
$versionsQuery = "SELECT `Id`, `Name`, `IsDefault` FROM `versions`";
$versionsResult = $db->query($versionsQuery);
$versions = [];
$defaultVersionId = null;

while ($row = $versionsResult->fetch_assoc()) {
    $versions[] = $row;
    if ($row['IsDefault'] == 1) {
        $defaultVersionId = $row['Id'];
    }
}

// Получение выбранной версии (из POST или значение по умолчанию)
$selectedVersionId = isset($_POST['version_id']) ? intval($_POST['version_id']) : $defaultVersionId;

// Получение модулей для выбранной версии
$modulesQuery = "SELECT `Prefix`, `Name` FROM `modules` WHERE `VersionId` = ?";
$modulesStmt = $db->prepare($modulesQuery);
$modulesStmt->bind_param("i", $selectedVersionId);
$modulesStmt->execute();
$modulesResult = $modulesStmt->get_result();
$modules = $modulesResult->fetch_all(MYSQLI_ASSOC);

// Получение таблиц, если выбран модуль
$selectedModule = isset($_POST['module']) ? $_POST['module'] : null;
$tables = [];
if ($selectedModule) {
    $tablesQuery = "SELECT `Name`, `Description`, `IsCompanyDependent`, `IsYearDependent`
                    FROM `tables` 
                    WHERE `VersionId` = ? AND `Module` = ?";
    $tablesStmt = $db->prepare($tablesQuery);
    $tablesStmt->bind_param("is", $selectedVersionId, $selectedModule);
    $tablesStmt->execute();
    $tablesResult = $tablesStmt->get_result();
    $tables = $tablesResult->fetch_all(MYSQLI_ASSOC);
}

// Получение полей, если выбрана таблица
$selectedTable = isset($_POST['table']) ? $_POST['table'] : null;
$fields = [];
if ($selectedTable) {
    $fieldsQuery = "SELECT `VersionId`, `Name`, `Type`, `Len`, `Precision`, `ScalaLen`, `Description`, `Note`, `PkOrder` 
                    FROM `fields` 
                    WHERE `VersionId` = ? AND `Table` = ?";
    $fieldsStmt = $db->prepare($fieldsQuery);
    $fieldsStmt->bind_param("is", $selectedVersionId, $selectedTable);
    $fieldsStmt->execute();
    $fieldsResult = $fieldsStmt->get_result();
    $fields = $fieldsResult->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-TL8WDNT5CM"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-TL8WDNT5CM');
    </script>
    <!-- End Google Analytics -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iScala Structure | Database Documentation</title>
    <!-- favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <!-- end favicon -->
    <meta name="description" content="Comprehensive documentation for iScala/Epicor database structure including tables, fields and modules">
    <meta name="keywords" content="iscala, scala, epicor, database, documentation, tables, fields, modules">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://db.apicosoft.ru">
    <style>
        * {
            font-family: 'Calibri', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 60px; /* Для фиксированного header */
        }
        
        .container {
            display: flex;
            flex-wrap: wrap;
            flex: 1;
        }
        
        /* HEADER - фиксированный */
        .header {
            background-color: #156a8d;
            color: #ffffff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            
            font-weight: bold;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .header select {
            padding: 5px;
            border-radius: 4px;
            border: none;
        }
        
        /* MODULES and TABLES */
        .modules {
            padding: 10px;
            width: 30%;
            min-width: 250px;
            max-width: 30%;
        }
        
        .tables {
            padding: 10px;
            flex: 1;
            min-width: 300px;
        }
        
        /* FIELDS */
        .fields {
            width: 100%;
            padding: 10px;
            scroll-margin-top: 60px; /* Компенсируем фиксированный header */
        }
        
       /* FOOTER */
        .footer {
    background-color: #515151;
    color: #ffffff;
    padding: 10px;
    display: flex;
    flex-wrap: wrap; /* Разрешаем перенос при необходимости */
    justify-content: space-between;
    width: 100%;
    font-family: 'Calibri';
}

/* Общие стили для всех колонок */
.footer-left,
.footer-center,
.footer-right {
    padding: 10px;
    box-sizing: border-box;
    text-align: center;
}

/* FOOTER Descktop al TR in line */
.footer-left { width: 20%; }
.footer-center { width: 60%; } /* Центральный блок шире */
.footer-right { width: 20%; }

/* Мобильные: вертикальное расположение */
@media (max-width: 768px) {
    .footer-left,
    .footer-center,
    .footer-right {
        width: 100%; /* На всю ширину */
        min-width: 0; /* Отменяем принудительную ширину */
    }
}

/* Остальные стили */
.footer-copyright {
    margin-bottom: 8px;
    font-size: 14px;
}

.footer-link {
    margin-bottom: 5px;
}

.footer-link a {
    color: #ffffff !important;
    text-decoration: none;
    font-size: 13px;
}
        
        /* Tables styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background-color: #FFFFFF !important;
            color: #156a8d !important;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 3px;
            border-bottom: 1pt solid #156a8d;
        }
        
        tr:nth-child(even) {
            background-color: #f0f8ff;
        }
        
        tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        tr:hover {
            background-color: #FF0000;
            color: #ffffff;
            cursor: pointer;
        }
        
        tr:hover td {
            color: #ffffff !important;
        }
        
        td {
            padding: 6px;
            color: #000000;
        }
        
        /* Mobile view */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .container {
                flex-direction: column;
            }
            
            .modules, .tables {
                width: 100%;
                max-width: 100%;
            }
            
            * {
                font-size: 12px;
            }
            
            th {
                letter-spacing: 1px;
                padding: 6px;
            }
        }

	/* Новые стили для фиксированной шапки таблицы FIELDS */
        .fields-table-container {
            position: relative;
            overflow: auto;
            max-height: 80vh;
            margin-top: 10px;
        }
        
        .fields-table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .fields-table-container thead th {
            position: sticky;
            top: 0;
            background-color: #ffffff !important;
            color: #156a8d !important;
            z-index: 10;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .fields-table-container tbody tr:nth-child(even) {
            background-color: #f0f8ff;
        }
        
        .fields-table-container tbody tr:nth-child(odd) {
            background-color: white;
        }
        
        /* Оптимизация для мобильных устройств */
        @media (max-width: 768px) {
            .fields-table-container {
                max-height: 50vh;
            }
        }

.fields-table-container tbody tr:hover {
    background-color: #FF0000;
    color: #ffffff;
    cursor: pointer;
}

        /* ARROW UP BUTTON START */
.scroll-to-top {
    position: fixed;
    bottom: 130px;
    right: 30px;
    width: 40px;
    height: 40px;
    background-color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    background-color: #ffffff;
    transform: translateY(-3px);
    border: 1px solid #FF0000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3), 0 0 0 1px #FF0000;
}
/* ARROW UP BUTTON END*/
    </style>
</head>



<body>
    <form method="post" id="mainForm" onsubmit="return submitForm(event)">
        <!-- HEADER -->
        <div class="header">
            <div>ApicoSOFT</div>
            <div>iScala Structure</div>
            <div>
                <select name="version_id" onchange="document.getElementById('mainForm').submit()">
                    <?php foreach ($versions as $version): ?>
                        <option value="<?= $version['Id'] ?>" <?= $version['Id'] == $selectedVersionId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($version['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="container">
            <!-- MODULES --> 
            <div class="modules">
                <table>
                    <tr><th colspan="2">MODULES</th></tr>
                    <?php foreach ($modules as $index => $module): ?>
                    <tr onclick="document.getElementById('moduleInput').value='<?= htmlspecialchars($module['Prefix']) ?>'; document.getElementById('mainForm').submit();">    
                            <td><?= htmlspecialchars($module['Prefix']) ?></td>
                            <td><?= htmlspecialchars($module['Name']) ?></td>
                            
                    </tr>
                    <?php endforeach; ?>
                </table>
                <input type="hidden" id="moduleInput" name="module" value="<?= htmlspecialchars($selectedModule) ?>">
            </div>  


            
        <!-- TABLES -->
        <div class="tables" id="tablesContainer">
            <?php if ($selectedModule): ?>
                <table>
                    <head>
                    <tr>
                    <th colspan="4">TABLES</th></tr><tr>
                    <th colspan="4">
                        <input type="text" id="tableSearch" placeholder="Search..." style="
                        font-size: 10px;
                        padding: 5px;
                        border: 1px solid #156a8d;
                        border-radius: 4px;
                        width: 200px;
                        float: center;
                        ">
                    </th>
                    </head>
                    
                    </tr>
                        <?php foreach ($tables as $index => $table): ?>
                        <tr onclick="sessionStorage.setItem('scrollToFields', 'true'); document.getElementById('tableInput').value='<?= htmlspecialchars($table['Name']) ?>'; document.getElementById('mainForm').submit();">    
                            <td><?= htmlspecialchars($table['Name']) ?></td>
                            <td><?= htmlspecialchars($table['Description']) ?></td>
                            <td>
                                <?= $table['IsYearDependent'] == 1 ? 'Year' : '' ?>  
                            </td>
                            <td>
                                <?= $table['IsCompanyDependent'] == 1 ? 'Company' : '' ?>
                            </td>   
                        </tr>
                        <?php endforeach; ?>
                        
                </table>
                <input type="hidden" id="tableInput" name="table" value="<?= htmlspecialchars($selectedTable) ?>">
            <?php endif; ?>
        </div>
        <!-- TABLES END-->
        <!-- LOOKING FOR TABLE - START -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('tableSearch');
                // Выбираем все строки таблицы, кроме заголовков (первой строки)
                const tableRows = document.querySelectorAll('.tables table tr:not(:first-child):not(:has(th))');
        
                if (searchInput && tableRows.length > 0) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                
                        tableRows.forEach(row => {
                            // Проверяем, что у строки есть ячейки
                            if (row.cells && row.cells.length >= 2) {
                                const name = row.cells[0].textContent.toLowerCase();
                                const description = row.cells[1].textContent.toLowerCase();
                        
                                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                            }
                        });
                    });
                }
            });
        </script>
        <!-- LOOKING FOR TABLE - END -->
        <!-- FIELDS --> 
        <div class="fields" id="fields">
            <?php if ($selectedTable): ?>
                <div class="fields-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>PK</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Len</th>
                                <th>Prec</th>
                                <th>ScalaLen</th>
                                <th>Note</th>
                            </tr>
                            <tr>
                                <th colspan="8" style="text-align: center; padding: 5px;">
                                    <input type="text" id="fieldSearch" placeholder="Search in fields..." style="
                                        font-size: 12px;
                                        padding: 5px;
                                        border: 1px solid #156a8d;
                                        border-radius: 4px;
                                        width: 60%;
                                        max-width: 400px;
                                    ">
                                </th>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $index => $field): ?>
                                <tr>
                                    <td align="center"><?= $field['PkOrder'] == 0 ? '' : $field['PkOrder'] ?></td>
                                    <td align="center"><?= htmlspecialchars($field['Name']) ?></td>
                                    <td><?= htmlspecialchars($field['Description']) ?></td>
                                    <td align="center"><?= htmlspecialchars($field['Type']) ?></td>
                                    <td align="center"><?= htmlspecialchars($field['Len']) ?></td>
                                    <td align="center"><?= htmlspecialchars($field['Precision']) ?></td>
                                    <td align="center"><?= htmlspecialchars($field['ScalaLen']) ?></td>
                                    <td><?= htmlspecialchars($field['Note']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div> 
        <!-- FIELDS END-->
        <!-- FOOTER -->  
        <div class="footer">
            <div class="footer-left">ISCALA SUPPORT & CUSTOMIZATION<br><br>Business processes implementation, ad-hock services, end-users training and education, maintenance support.</div>
            <div class="footer-center">
                <div>2009 - <?= $currentYear ?> Apicosoft LLC | ООО "Апикософт"<br><br></div>
                <div class="footer-copyright">CONTACT US:</div>
                <div class="footer-link"><a href="mailto:info@apicosoft.ru">info@apicosoft.ru</a></div>
                <div class="footer-link"><a href="https://apicosoft.ru">apicosoft.ru</a></div>
                <div>
                    <br><a href="https://apicosoft.ru">
                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                        width="40" 
                        height="40"
                        alt="Описание">
                        </a>
                </div>
            </div>
            <div class="footer-right">EPICORE SERVICE CONNECT<br><br>Integration with any third-party services. Customize internal workflows.
            <br><br>REPORTING <br><br>SAP Crystal Reports, SQL Server Reporting Services, Excel, 1С.
            </div>
        </div>
        <!-- FOOTER END -->
    </form>
<!-- Looking for fields - start -->
<script>

document.addEventListener('DOMContentLoaded', function() {
    const fieldSearchInput = document.getElementById('fieldSearch');
    const fieldRows = document.querySelectorAll('.fields-table-container tbody tr');
    
    if (fieldSearchInput && fieldRows.length > 0) {
        fieldSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            fieldRows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase(); // Name (2-я ячейка)
                const description = row.cells[2].textContent.toLowerCase(); // Description
                const note = row.cells[7].textContent.toLowerCase(); // Note (8-я ячейка)
                
                if (name.includes(searchTerm) || 
                    description.includes(searchTerm) || 
                    note.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
<!-- Looking for fields - end -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Если нужно скроллить к полям
    if (sessionStorage.getItem('scrollToFields') === 'true') {
        sessionStorage.removeItem('scrollToFields');
        
        // Ждем немного для полной загрузки DOM
        setTimeout(() => {
            const fieldsSection = document.getElementById('fields');
            if (fieldsSection) {
                // Плавный скролл с учетом фиксированного header
                window.scrollTo({
                    top: fieldsSection.offsetTop - 60,
                    behavior: 'smooth'
                });
            }
        }, 100);
    }
});
</script>

<!-- BUTTON UP - START -->
<a href="#" id="scrollToTop" class="scroll-to-top"><img src="img/arrowup.png" alt="Наверх" width="25" height="25"></a>
<!-- BUTTON UP - END -->

<!-- SCRIPT ARROW UP BUTTON - START -->

<script>   
document.addEventListener('DOMContentLoaded', function() {
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (scrollToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
</script>

<!-- SCRIPT ARROW UP BUTTON - START -->

</body>
</html>
<?php
$db->close();
?>
