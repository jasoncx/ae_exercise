<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Portfolio
 *
 * @ORM\Table(name="portfolio",uniqueConstraints={@ORM\UniqueConstraint(name="user_id_portfolio",columns={"user_id","name"})})
 * @UniqueEntity(fields={"userId","name"}, message="Portfolio name already exists")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PortfolioRepository")
 */
class Portfolio
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="portfolios")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="Stock", mappedBy="portfolio", cascade={"remove"})
     */
    private $stocks;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return Portfolio
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Portfolio
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Portfolio
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->stocks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add stock
     *
     * @param \AppBundle\Entity\Stock $stock
     *
     * @return Portfolio
     */
    public function addStock(\AppBundle\Entity\Stock $stock)
    {
        $this->stocks[] = $stock;

        return $this;
    }

    /**
     * Remove stock
     *
     * @param \AppBundle\Entity\Stock $stock
     */
    public function removeStock(\AppBundle\Entity\Stock $stock)
    {
        $this->stocks->removeElement($stock);
    }

    /**
     * Get stocks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStocks()
    {
        return $this->stocks;
    }

    /**
     * Get performance data for all stocks in portfolio
     * Retrieve stock performance data from api
     * Can optimize by checking / storing stock data into db and fetching only missing / latest dates
     *
     * @return array
     */
    public function getPerformanceData() {
        $stocks = $this->getStocks();

        // get data array indexed by dates
        $dates = array();
        $start = strtotime('2 years ago');
        $end = strtotime('today');
        while($start <= $end)
        {
            $dates[date('Y-m-d', $start)] = 0;
            $start = strtotime('+ 1 day', $start);
        }
        // set up api query dates, split by 1 year each due to query limitations
        $ranges = array(
            array('start' => date('Y-m-d', strtotime('2 years ago')), 'end' => date('Y-m-d', strtotime('1 year ago'))),
            array('start' => date('Y-m-d', strtotime('1 year ago + 1 day')), 'end' => date('Y-m-d', strtotime('yesterday')))
        );

        // async requests
        $mh = curl_multi_init(); 

        $urls = array();
        $curl_array = array();
        $count = 0;
        foreach ($stocks as $stock)
        {
            foreach ($ranges as $i => $range) 
            { 
                $url = 'https://query.yahooapis.com/v1/public/yql'.
                    '?q='.urlencode('select * from yahoo.finance.historicaldata where symbol = "'.$stock->getSymbol().'" and startDate = "'.$range['start'].'" and endDate = "'.$range['end'].'"').
                    '&format=json&diagnostics=true&env=store://datatables.org/alltableswithkeys';
                $urls[$count] = $url;
                $curl_array[$count] = curl_init($url);
                curl_setopt($curl_array[$count], CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($mh, $curl_array[$count]);
                ++$count;
            }
        }

        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        // parse results and combine closing quotes for all stocks
        $res = array();
        foreach ($urls as $i => $url)
        {
            $result = json_decode($json=curl_multi_getcontent($curl_array[$i]), $assoc=true);
            curl_multi_remove_handle($mh, $curl_array[$i]);
            $quotes = isset($result['query']) && isset($result['query']['results']) && isset($result['query']['results']['quote']) ? $result['query']['results']['quote'] : null;
            if ($quotes) 
            {
                foreach ($quotes as $quote) 
                {
                    if (isset($quote['Date']) && isset($quote['Close']) && isset($dates[$quote['Date']])) 
                    {
                        $dates[$quote['Date']] += floatval($quote['Close']);
                    }
                }
            }
        }
        curl_multi_close($mh);

        // backfill weekends (and potentially bad data points, this scenario can be addressed also)
        $prev = null;
        foreach ($dates as $date => $val) 
        {
            if (!$val) 
            {
                if ($prev) 
                {
                    $dates[$date] = $dates[$prev];
                    $prev = $date;
                } else {
                    unset($dates[$date]);
                }
            } else {
                $prev = $date;
            }
        }

        return $dates;
    }
}
