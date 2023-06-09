<?php
require_once 'partials/builder-viewer-header.php';

if(isset($_GET['question']))
{
    echo <<<_END
    <script>
    google.charts.load('current', {'packages':['corechart']});
    
    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);

    // Callback that creates and populates a data table,
    // instantiates the pie chart, passes in the data and
    // draws it.
    function drawChart() {

    // Create the data table.
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Answer');
    data.addColumn('number', 'No. of occurrences');
    data.addRows([
_END;
    
    if($result = mysqli_query($con, "SELECT answer, questions.title AS title, count(answer) as countAns FROM answers INNER JOIN questions on questions.id = answers.question INNER JOIN surveys on surveys.id = questions.survey WHERE surveys.id = '$_SESSION[surveyID]' and answers.answer != ' ' and questions.title = '$_GET[question]' GROUP BY answer"))
    {
        for($i =0; $i<mysqli_num_rows($result); $i++)
        {
            $row = mysqli_fetch_assoc($result);
            $str = "['$row[answer]', ". $row['countAns']. '],';
            echo $str;
        }
    }
    
    echo<<<_END
        ]);

        // Set chart options
        var options = {'title':'$row[title]',
                        'width':400,
                        'height':300,
                    };

        var chart = new google.visualization.PieChart(document.getElementById('chart_div1'));
        chart.draw(data, options);
        }
        </script>
_END;
}

if(isset($_SESSION['surveyID']))
{
    if(isset($_SESSION['owner']))
    {
        if($_SESSION['owner'])
        {
            $numofResponses = mysqli_query($con, "SELECT count(answer) AS numberOfResponses FROM `answers` INNER JOIN questions on answers.question=questions.id INNER JOIN surveys on questions.survey=surveys.id WHERE surveys.id=$_SESSION[surveyID]");
            $answersrow = mysqli_fetch_assoc($numofResponses);

            if($answersrow['numberOfResponses']>0)
            {                
                echo "
                <section>
                    <h2>Responses</h2>
                        <table>
                        <tr>";

                        $resultTitles = mysqli_query($con, "SELECT DISTINCT title FROM questions INNER JOIN answers ON answers.question = questions.id WHERE questions.survey = $_SESSION[surveyID]");

                        for($i =0; $i<mysqli_num_rows($resultTitles); $i++)
                        {
                            $row = mysqli_fetch_assoc($resultTitles);
                            echo"<th>$row[title]</th>";
                        }
                        echo "</tr><tr>";

                        $resultData = mysqli_query($con, "SELECT answer FROM answers INNER JOIN questions ON answers.question = questions.id WHERE questions.survey = $_SESSION[surveyID] ORDER BY answers.id");

                        for($i=0; $i<mysqli_num_rows($resultData); $i++)
                        {
                            $row = mysqli_fetch_assoc($resultData);
                            
                            if($i % (mysqli_num_rows($resultTitles))==0 && $i>0)
                            {
                                echo "</tr><tr>";
                                echo"<td>$row[answer]</td>";
                            }
                            else{
                                echo"<td>$row[answer]</td>";
                            }
                        }
                        echo "</tr>";

                        $noOfRespondants = round($answersrow['numberOfResponses']/mysqli_num_rows($resultTitles));

                    echo"
                        </table>
                        <p>No. of respondants: $noOfRespondants</p>
                        <a href=create-csv.php?id=$_SESSION[surveyID]>Export CSV</a>
                </section>";

                echo<<<_END
                <section>
                    <h2>Graphs</h2>
                    <script type="text/javascript">
                        // Load the Visualization API and the corechart package.
                        google.charts.load('current', {'packages':['corechart', 'controls']});
                
                        // Set a callback to run when the Google Visualization API is loaded.
                        google.charts.setOnLoadCallback(drawChart);
                
                        // Callback that creates and populates a data table,
                        // instantiates the pie chart, passes in the data and
                        // draws it.
                        function drawChart() {
                
                        // Create the data table.
                        var data = new google.visualization.DataTable();
                        data.addColumn('string', 'Questions');
                        data.addColumn('number', 'No. of people responded');
                        data.addRows([
_END;
                        $result = mysqli_query($con, "SELECT title, COUNT(answer) as numberResponses FROM questions INNER JOIN answers ON answers.question = questions.id WHERE questions.survey = $_SESSION[surveyID] and answers.answer != ' ' GROUP BY title");
                        
                        for($k=0; $k<mysqli_num_rows($result); $k++)
                        {
                            $row = mysqli_fetch_assoc($result);
                            $str = "['$row[title]', ". $row['numberResponses']. '],';
                            echo $str;
                        }
                        
                        $result = mysqli_query($con, "SELECT surveyName FROM surveys WHERE id='$_SESSION[surveyID]'");
                        $row = mysqli_fetch_assoc($result);
                        $title = $row['surveyName'];
                        echo"
                        ]);

                        // Create a dashboard.
                            var dashboard = new google.visualization.Dashboard(
                            document.getElementById('dashboard_div'));
                    
                        // Create a range slider, passing some options
                               var slider = new google.visualization.ControlWrapper({
                              'controlType': 'NumberRangeFilter',
                              'containerId': 'filter_div',
                              'options': {
                              'filterColumnLabel': 'No. of people responded',
                              textStyle:{color: '#FFF'}
                              }
                            });
                        
                        // set pie chart options
                        var pieChart = new google.visualization.ChartWrapper({
                               'chartType': 'PieChart',
                               'containerId': 'pie_div',
                               'options': {
                                   'title':'$title question breakdown',
                                   'width': 600,
                                   'height': 300,
                                   'pieSliceText': 'value',
                                   'legend': 'right'
                                }
                        }); 
                        
                        // Establish dependencies, declaring that 'filter' drives 'pieChart',
                        // so that the pie chart will only display entries that are let through
                        // given the chosen slider range.
                        dashboard.bind(slider, pieChart);
                        dashboard.draw(data);
                        }
                        
                    </script>

                    <div id='dashboard_div'>
                        <div id='pie_div'></div>
                        <div id='filter_div'></div>
                    </div>

                    
                    <select id='questionSelector'>";
                        echo"<option> </option>";
                    $resultTitles = mysqli_query($con, "SELECT DISTINCT title FROM questions INNER JOIN answers ON answers.question = questions.id WHERE questions.survey = $_SESSION[surveyID]");
                    for($i =0; $i<mysqli_num_rows($resultTitles); $i++)
                    {
                        $row = mysqli_fetch_assoc($resultTitles);
                        echo"<option>$row[title]</option>";
                    }

            

                echo "</select>

                    <script type='text/javascript'>

                    $(document).ready(function () {
                        var optionText = $('#questionSelector').val()
                        $('#questionSelector').on('change',function(){
                            optionText = $('#questionSelector option:selected').val();
                            window.location.replace('view-responses.php?question='+optionText);
                        })
                    });
                
                    
                    </script>
                    <div id='chart_div1'></div>
                </section>";
            }
            else
            {
                echo "<section><h2>No one has got back to you 😢</h2><br/><p>You may want to consider sharing the survey if it isn't already</p></section>";
            }
        }
        else
        {
            header('Location: view-surveys.php');
        }
    }
    else
    {
        header('Location: admin.php');
    }
}
else
{
    header('Location: view-surveys.php');
}

?>